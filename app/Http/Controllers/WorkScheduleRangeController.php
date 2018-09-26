<?php

namespace App\Http\Controllers;

use App\WorkScheduleRange;
use Illuminate\Http\Request;

class WorkScheduleRangeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return WorkScheduleRange::with('workSchedule')->get();
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
            'WorkScheduleId' => 'required|exists:sqlsrv.cfg.WorkSchedules,id',
            'Start' => 'required',
            'End' => 'required'
        ]);

        $workScheduleRange = new WorkScheduleRange($request->all());
        $workScheduleRange->save();

        return response()->json($workScheduleRange, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return WorkScheduleRange::findOrFail($id);
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
        $workScheduleRange = WorkScheduleRange::findOrFail($id);
        $request->validate([
            'WorkScheduleId' => 'exists:sqlsrv.cfg.WorkSchedules,id'
        ]);

        $workScheduleRange->fill($request->all());
        $workScheduleRange->save();

        return response()->json($workScheduleRange, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $workScheduleRange = WorkScheduleRange::findOrFail($id);
        try {
            $workScheduleRange->delete();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Error, no se puede eliminar el registo, verifique que no tenga datos asociados al mismo'
            ], 400);
        }

        return response()->json([
            'message' => 'Registro eliminado con éxito'
        ], 200);
    }
}
