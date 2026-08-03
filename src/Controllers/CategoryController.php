<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Category;
use App\Models\CategoryInput;
use App\Models\CategoryType;
use App\Repositories\CategoryRepository;
use App\Services\AuthService;
use PDOException;

final class CategoryController
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly AuthService $auth,
        private readonly View $view,
        private readonly Session $session,
    ) {
    }

    public function index(): Response
    {
        return $this->view->render('categories/index', [
            'title' => 'Categorias',
            'categories' => $this->categories->allForUser($this->auth->requireUserId()),
            'status' => $this->session->pullFlash('status'),
            'error' => $this->session->pullFlash('error'),
            'errors' => $this->session->pullFlash('errors') ?? [],
            'old' => $this->session->pullFlash('old') ?? [],
        ]);
    }

    public function store(Request $request): Response
    {
        $name = trim($request->input('name'));
        $color = $request->input('color', Category::DEFAULT_COLOR);
        $type = CategoryType::tryFrom($request->input('type'));

        $validator = new Validator();
        $validator->label('name', $name);
        $validator->color('color', $color);
        if ($type === null) {
            $validator->fail('type', 'Escolha um tipo válido.');
        }
        if ($validator->fails()) {
            $this->session->flash('errors', $validator->errors());
            $this->session->flash('old', ['name' => $name, 'color' => $color]);

            return Response::redirect('/categories');
        }

        try {
            $this->categories->create(
                $this->auth->requireUserId(),
                $type,
                new CategoryInput($name, $color),
            );
            $this->session->flash('status', 'Categoria criada.');
        } catch (PDOException $e) {
            $this->rethrowUnlessConstraint($e);
            $this->session->flash('error', 'Já existe uma categoria com esse nome e tipo.');
        }

        return Response::redirect('/categories');
    }

    public function edit(int $id): Response
    {
        $category = $this->categories->find($this->auth->requireUserId(), $id);
        if ($category === null) {
            $this->session->flash('error', 'Categoria não encontrada.');

            return Response::redirect('/categories');
        }

        return $this->view->render('categories/edit', [
            'title' => 'Editar categoria',
            'category' => $category,
            'errors' => $this->session->pullFlash('errors') ?? [],
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $name = trim($request->input('name'));
        $color = $request->input('color', Category::DEFAULT_COLOR);

        $validator = new Validator();
        $validator->label('name', $name);
        $validator->color('color', $color);
        if ($validator->fails()) {
            $this->session->flash('errors', $validator->errors());

            return Response::redirect("/categories/{$id}/edit");
        }

        $input = new CategoryInput($name, $color);

        try {
            if ($this->categories->update($this->auth->requireUserId(), $id, $input)) {
                $this->session->flash('status', 'Categoria atualizada.');
            } else {
                $this->session->flash('error', 'Categoria não encontrada.');
            }
        } catch (PDOException $e) {
            $this->rethrowUnlessConstraint($e);
            $this->session->flash('error', 'Já existe uma categoria com esse nome e tipo.');
        }

        return Response::redirect('/categories');
    }

    public function destroy(int $id): Response
    {
        try {
            if ($this->categories->delete($this->auth->requireUserId(), $id)) {
                $this->session->flash('status', 'Categoria excluída.');
            } else {
                $this->session->flash('error', 'Categoria não encontrada.');
            }
        } catch (PDOException $e) {
            $this->rethrowUnlessConstraint($e);
            $this->session->flash('error', 'Categoria em uso por transações — recategorize antes de excluir.');
        }

        return Response::redirect('/categories');
    }

    private function rethrowUnlessConstraint(PDOException $e): void
    {
        if ((string) $e->getCode() !== '23000') {
            throw $e;
        }
    }
}
