<?php

namespace Api\Services;

use Api\Models\Module;
use Api\Services\BaseService;

class ModuleService extends BaseService
{
    public function find($id)
    {
        $module = Module::find($id);
        return $module;
    }

    public function findOrFail($id)
    {
        $module = Module::find($id);
        if ($module === null) {
            api_abort_404('Module');
        }
        return $module;
    }
}
