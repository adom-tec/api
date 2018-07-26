<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\QualityQuestion;

class QualityQuestionController extends Controller
{
    public function index()
    {
        return QualityQuestion::all();
    }
}
