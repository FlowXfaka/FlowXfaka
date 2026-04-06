<?php

namespace App\Http\Controllers;

use App\Services\ApplicationInstaller;
use App\Services\InstallationRequirements;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class InstallController extends Controller
{
    public function __construct(
        private readonly ApplicationInstaller $installer,
        private readonly InstallationRequirements $requirements,
    ) {
    }

    public function show(): Response
    {
        return response()->view('install', $this->viewData());
    }

    public function store(Request $request): Response
    {
        $input = $this->installer->prepareInput($request->all());
        $validator = $this->installer->makeValidator($input, true);

        if ($validator->fails()) {
            return response()->view('install', $this->viewData($input, $validator->errors()->all()), 422);
        }

        try {
            $result = $this->installer->install($validator->validated());
        } catch (Throwable $exception) {
            return response()->view('install', $this->viewData($input, [$exception->getMessage()]), 500);
        }

        return response()->view('install', $this->viewData($input, [], $result), 201);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $errors
     * @param  array<string, mixed>|null  $result
     * @return array<string, mixed>
     */
    private function viewData(array $values = [], array $errors = [], ?array $result = null): array
    {
        $defaults = array_merge($this->installer->defaults(), [
            'admin_name' => '',
            'db_host' => '',
            'db_port' => '',
            'db_database' => '',
            'db_username' => '',
        ]);

        return [
            'values' => array_merge($defaults, $values),
            'errors' => $errors,
            'report' => $this->requirements->report(),
            'result' => $result,
        ];
    }
}
