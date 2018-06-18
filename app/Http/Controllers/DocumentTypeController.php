<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DocumentType;

class DocumentTypeController extends Controller
{
    public function index()
    {
        return DocumentType::all();
    }
}
