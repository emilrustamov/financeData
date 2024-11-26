<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\Http\Request;

class RecordApiController extends Controller
{
    public function index(Request $request)
    {
        // Получаем все записи, можно добавить фильтрацию, сортировку, пагинацию
     
        return response()->json(Record::all());
    }
}
