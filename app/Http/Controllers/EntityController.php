<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Entity;

class EntityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Entity::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'Nit' => 'required',
            'BusinessName' => 'required',
            'Code' => 'required',
            'Name' => 'required' 
        ]);

        $entity = new Entity($request->all());
        $entity->save();
        return response()->json($entity, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Entity::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $entity =  Entity::findOrFail($id);

        $entity->fill($request->all());
        $entity->save();

        return response()->json($entity, 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $entity = Entity::findOrFail($id);
        try {
            $entity->delete();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error, La entidad no puede ser eliminada, verifique que no tenga datos asociados a este registro'
            ], 400);
        }
        return response()->json([
            'message' => 'Entidad borrada con exito'
        ], 200);
    }
}
