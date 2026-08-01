<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\CategoryType;
use App\Models\Transaction;
use App\Services\TransactionCsvExporter;
use PHPUnit\Framework\TestCase;

final class TransactionCsvExporterTest extends TestCase
{
    public function testExportsBomHeaderAndSemicolonRows(): void
    {
        $csv = TransactionCsvExporter::export([
            $this->tx(CategoryType::Expense, 8990, 'Feira da semana', '2026-07-15'),
        ]);

        $this->assertStringStartsWith("\u{FEFF}", $csv);
        $lines = explode("\n", trim(str_replace("\u{FEFF}", '', $csv)));
        $this->assertSame('Data;Descrição;Categoria;Conta;Tipo;"Valor (R$)"', trim($lines[0]));
        $this->assertSame('15/07/2026;"Feira da semana";Mercado;Carteira;Despesa;-89,90', trim($lines[1]));
    }

    public function testFormatsIncomeAsPositive(): void
    {
        $csv = TransactionCsvExporter::export([
            $this->tx(CategoryType::Income, 500000, 'Pagamento', '2026-07-05'),
        ]);

        $this->assertStringContainsString(';5.000,00', $csv);
        $this->assertStringNotContainsString('-5.000,00', $csv);
    }

    public function testGuardsFormulaInjectionInTextCells(): void
    {
        $csv = TransactionCsvExporter::export([
            $this->tx(CategoryType::Expense, 100, '=SUM(A1:A9)', '2026-07-01'),
        ]);

        $this->assertStringContainsString("'=SUM(A1:A9)", $csv);
    }

    private function tx(CategoryType $type, int $cents, string $description, string $date): Transaction
    {
        return new Transaction(
            id: 1,
            userId: 1,
            accountId: 1,
            categoryId: 1,
            type: $type,
            amountCents: $cents,
            description: $description,
            date: $date,
            categoryName: 'Mercado',
            accountName: 'Carteira',
        );
    }
}
