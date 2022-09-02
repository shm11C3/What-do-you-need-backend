<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Return a list of categories
     *
     * @return void
     */
    public function getCategories()
    {
        $data = DB::table('post_categories')->get();

        return response()->json($data);
    }
}
