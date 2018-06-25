<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notice;

class NoticeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Notice::with('user')
            ->orderBy('CreationDate', 'desc')
            ->get();
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
            'NoticeTitle' => 'required',
            'NoticeText' => 'required'
        ]);

        $notice = new Notice($request->all());
        $notice->UserId = $request->user()->UserId;
        $notice->save();
        $notice->load('user');
        return response()->json($notice, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $notice = Notice::findOrFail($id);
        try {
            $notice->delete();
            return response()->json([
                'message' => 'Noticia eliminada con éxito'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error, no se pudo borrar la noticia, verifique que no hayan relaciones asociadas a este registro'
            ], 200);
        }
    }
}
