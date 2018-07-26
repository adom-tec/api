<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PlanEntity;
use App\Entity;

class PlanEntityController extends Controller
{
    public function index($entity)
    {
        return PlanEntity::where('EntityId', $entity)->get();
    }

    public function store($entity, Request $request)
    {
        $entity = Entity::findOrFail($entity)->EntityId;
        $request->validate([
            'Name' => 'required',
            'State' => 'boolean'
        ]);

        $planEntity = new PlanEntity($request->all());
        $planEntity->EntityId = $entity;
        $planEntity->save();
        return response()->json($planEntity, 201);
    }

    public function update(Request $request, $entity, $id)
    {
        $planEntity = PlanEntity::findOrFail($id);
        $request->validate([
            'State' => 'boolean'
        ]);

        $planEntity->fill($request->all());
        $planEntity->save();
        return response()->json($planEntity, 200);
    }
}
