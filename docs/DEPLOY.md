# Deploy na Oracle Cloud Always Free

Runbook para colocar o Fluxo no ar numa VM gratuita da Oracle, com HTTPS válido e
banco persistente. Do zero ao site funcionando leva uns 30 minutos, a maior parte
esperando a Oracle provisionar a máquina.

O que você vai ter no final: `https://seu-nome.duckdns.org` servido por Caddy, com
certificado Let's Encrypt renovado sozinho, SQLite num volume Docker, e nenhum
usuário pré-cadastrado.

## Índice

1. [Criar a VM](#1-criar-a-vm)
2. [Abrir as portas — as duas camadas](#2-abrir-as-portas--as-duas-camadas)
3. [Registrar o subdomínio no DuckDNS](#3-registrar-o-subdomínio-no-duckdns)
4. [Instalar o Docker](#4-instalar-o-docker)
5. [Subir a aplicação](#5-subir-a-aplicação)
6. [Verificar](#6-verificar)
7. [Operação do dia a dia](#7-operação-do-dia-a-dia)
8. [Quando algo dá errado](#8-quando-algo-dá-errado)

## 1. Criar a VM

Em [cloud.oracle.com](https://cloud.oracle.com), crie a conta gratuita. Pede cartão
para verificação de identidade; a conta Always Free não cobra enquanto você ficar
dentro dos limites dela.

**Compute → Instances → Create instance:**

| Campo | Valor |
|---|---|
| Image | Canonical Ubuntu 24.04 |
| Shape | `VM.Standard.A1.Flex` (Ampere ARM) |
| OCPUs / memória | 4 e 24 GB — o teto do Always Free, tudo numa instância só |
| SSH keys | cole sua chave pública, ou deixe a Oracle gerar e baixe a privada |

> A cota de A1 costuma estar esgotada nas regiões mais movimentadas ("Out of host
> capacity"). Se acontecer, tente outra region na criação da conta, ou pegue o
> shape AMD `VM.Standard.E2.1.Micro`, que também é Always Free — 1 OCPU e 1 GB dão
> conta desse app com folga.

Anote o **Public IP address** da instância quando ela ficar `Running`.

## 2. Abrir as portas — as duas camadas

Esta é a etapa que trava praticamente todo deploy na Oracle. Existem **dois**
firewalls independentes e ambos bloqueiam 80/443 por padrão. Abrir só um deles
resulta em `curl` pendurado até dar timeout, sem mensagem de erro útil.

### Camada 1 — Security List da VCN

**Networking → Virtual Cloud Networks →** sua VCN **→ Security Lists →** Default
Security List **→ Add Ingress Rules.** Crie duas regras:

| Source CIDR | Protocol | Destination Port |
|---|---|---|
| `0.0.0.0/0` | TCP | `80` |
| `0.0.0.0/0` | TCP | `443` |

### Camada 2 — iptables da própria VM

A imagem Ubuntu da Oracle vem com `iptables-persistent` e uma regra `REJECT` no
fim da cadeia INPUT. Conecte na máquina e insira as exceções antes dela:

```bash
ssh ubuntu@SEU_IP

sudo iptables -I INPUT 1 -p tcp --dport 80 -j ACCEPT
sudo iptables -I INPUT 1 -p tcp --dport 443 -j ACCEPT
sudo netfilter-persistent save
```

O `-I INPUT 1` insere no topo, garantindo que a regra venha antes do `REJECT`.
O `netfilter-persistent save` é o que faz sobreviver ao reboot — sem ele, você
descobre o problema de novo na próxima vez que a máquina reiniciar.

> Detalhe técnico: o Docker publica portas via DNAT, e esse tráfego passa pela
> cadeia FORWARD, não pela INPUT — então em muitos casos funciona mesmo sem as
> regras acima. Adicione assim mesmo: é inofensivo, e o modo de falha quando
> falta é silencioso demais para valer o risco.

## 3. Registrar o subdomínio no DuckDNS

Em [duckdns.org](https://www.duckdns.org), entre com Google/GitHub. Digite o nome
desejado em **add domain** (ex.: `fluxo`), cole o IP público da VM no campo
**current ip** e clique em **update ip**.

Confirme que propagou antes de seguir — o Caddy precisa do DNS resolvendo para
conseguir o certificado:

```bash
dig +short fluxo.duckdns.org
```

Tem que devolver o IP da sua VM.

> Por que DuckDNS e não sslip.io: `duckdns.org` está no Public Suffix List, então
> cada subdomínio tem cota própria de certificados no Let's Encrypt. O `sslip.io`
> não está, e todos os usuários dele dividem uma cota semanal única que
> [já estourou várias vezes](https://github.com/cunnie/sslip.io/issues/108).

## 4. Instalar o Docker

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
newgrp docker
```

O `usermod` evita ter que usar `sudo` em todo comando docker. O `newgrp` aplica o
grupo na sessão atual sem precisar deslogar.

## 5. Subir a aplicação

```bash
git clone https://github.com/Italoneri/Gestor-de-finan-as.git fluxo
cd fluxo

cp .env.deploy.example .env.deploy
nano .env.deploy   # troque APP_DOMAIN pelo seu subdomínio do DuckDNS

docker compose --env-file .env.deploy -f docker-compose.prod.yml up -d --build
```

O build leva alguns minutos na primeira vez. Na subida, o entrypoint gera o `.env`
da aplicação, roda as migrations e cria o diretório de sessões. O seed **não** roda
em produção, então o banco sobe vazio — você cria sua conta pela tela de cadastro.

Esse comando é longo e você vai repetir bastante. Vale um alias:

```bash
echo "alias fluxo='docker compose --env-file .env.deploy -f docker-compose.prod.yml'" >> ~/.bashrc
source ~/.bashrc
```

Daqui em diante o runbook usa `fluxo logs`, `fluxo exec`, etc.

## 6. Verificar

**Certificado emitido:**

```bash
fluxo logs caddy | grep -i "certificate obtained"
```

A emissão acontece no primeiro acesso ao domínio, então pode ser preciso abrir o
site uma vez antes de a linha aparecer.

**HTTPS respondendo com cert válido** (sem `-k`, que é justamente o ponto):

```bash
curl -I https://fluxo.duckdns.org
```

Espera-se um `302` redirecionando para `/login`.

**Banco vazio** — prova de que o gate do seed pegou:

```bash
fluxo exec app php -r 'var_dump((new PDO("sqlite:storage/app.sqlite"))->query("select count(*) from users")->fetchColumn());'
```

Tem que sair `string(1) "0"`. Se sair `1`, o `teste@exemplo.com` foi criado e você
está com uma credencial pública ativa — confira se `APP_ENV` está mesmo `prod`.

**IP real chegando na aplicação:**

```bash
fluxo exec app tail -n 5 /var/log/apache2/access.log
```

Tem que mostrar o IP de onde você acessou, não `10.89.0.x`. Se aparecer o
endereço interno do Caddy, o `mod_remoteip` não carregou e o rate limiter de login
está contando as falhas de todo mundo num balde só.

**Sessão sobrevive a restart:** faça login, rode `fluxo restart app`, recarregue a
página. Você continua logado.

## 7. Operação do dia a dia

### Atualizar o código

```bash
cd ~/fluxo
git pull
fluxo up -d --build
```

O `--build` não é opcional: o `opcache.validate_timestamps=0` do
`docker/php.ini` faz o PHP ignorar mudanças em arquivo, então código novo só entra
por imagem nova.

### Backup do banco

O SQLite fica num volume Docker. Confirme o nome dele:

```bash
docker volume ls | grep finance-storage
```

Use o comando `.backup` do sqlite3 — ele tira uma cópia consistente com o banco em
uso, coisa que um `cp` do arquivo não garante:

```bash
sudo apt install -y sqlite3
mkdir -p ~/backups

sudo sqlite3 /var/lib/docker/volumes/fluxo_finance-storage/_data/app.sqlite \
  ".backup '$HOME/backups/app-$(date +%F).sqlite'"
```

Para automatizar, `crontab -e` e uma linha diária às 3h:

```cron
0 3 * * * sqlite3 /var/lib/docker/volumes/fluxo_finance-storage/_data/app.sqlite ".backup '/home/ubuntu/backups/app-$(date +\%F).sqlite'"
```

O `%` precisa de escape no crontab. Rode como root (`sudo crontab -e`) por causa
da permissão em `/var/lib/docker`.

### Recuperar senha

O app usa o `LogMailer`: nenhum e-mail sai de verdade, o link vai para um arquivo.
Peça o reset pela tela normal e depois pegue o link:

```bash
fluxo exec app tail -n 40 storage/mail.log
```

Reset de senha é o único fluxo que depende de e-mail — o cadastro não pede
confirmação nenhuma. Se um dia quiser e-mail de verdade, é só implementar
`MailerInterface` (`src/Services/MailerInterface.php`) com SMTP e trocar a
instância em `public/index.php`.

### Logs

| O quê | Onde |
|---|---|
| Erros da aplicação | `fluxo exec app tail -f storage/app.log` |
| Acesso HTTP | `fluxo exec app tail -f /var/log/apache2/access.log` |
| Certificado / proxy | `fluxo logs -f caddy` |

`storage/app.log` e `storage/mail.log` crescem sem rotação. Num disco de 200 GB
isso demora muito para incomodar, mas dê uma olhada de vez em quando.

## 8. Quando algo dá errado

**O site não carrega, `curl` fica pendurado até o timeout.**
Uma das duas camadas de firewall da [seção 2](#2-abrir-as-portas--as-duas-camadas)
está fechada. Teste de fora: `nc -vz SEU_IP 443`. Depois confira
`sudo iptables -L INPUT -n --line-numbers` e a Security List no console da Oracle.

**Caddy não consegue o certificado.**
Veja o erro real com `fluxo logs caddy`. As causas de sempre:

- DNS ainda não propagou — confirme com `dig +short seu-dominio.duckdns.org`.
- Porta **80** fechada. O desafio HTTP-01 do Let's Encrypt usa a 80, não a 443.
  Abrir só a 443 não funciona.
- `APP_DOMAIN` no `.env.deploy` diferente do domínio que você registrou.

**502 Bad Gateway.**
O Caddy está de pé mas o app não. `fluxo logs app` mostra o motivo — normalmente
uma migration que falhou ou permissão em `storage/`.

**Perdi o acesso e não tenho conta.**
O cadastro é aberto e entra direto, sem confirmação de e-mail: acesse `/register`
e crie uma. Como é aberto de verdade, qualquer um que descobrir o domínio também
cria — se quiser fechar depois, remova as rotas `/register` de `public/index.php`.
