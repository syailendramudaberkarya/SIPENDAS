<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\View\View;

trait RendersCrudViews
{
    protected function listing($records, array $columns, string $q): View
    {
        return view('admin.crud.index', [
            'records' => $records, 'columns' => $columns, 'q' => $q,
            'title' => $this->title, 'resource' => $this->resource,
            'deactivate' => in_array($this->resource, ['users', 'teachers', 'students'], true),
        ]);
    }

    protected function form($record, array $fields): View
    {
        return view('admin.crud.form', [
            'record' => $record, 'fields' => $fields,
            'title' => $this->title, 'resource' => $this->resource,
        ]);
    }
}
