<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\QualityAnswer;

class QualityAnswerController extends Controller
{
    public function index()
    {
        return QualityAnswer::all();
    }
}
