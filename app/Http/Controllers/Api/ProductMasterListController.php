<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductMasterList;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class ProductMasterListController extends Controller
{
    public function index()
    {
        $products = ProductMasterList::all();

        return response()->json([
            'data' => $products,
        ]);
    }

    public function upload(Request $request)
    {
        $file   = $request->file('file');
        $data   = Excel::toArray(new class implements WithHeadingRow
        {}, $file)[0];
        $record = [];

        foreach ($data as $row) {
            $record['product_id'] = $row['product_id'];
            $record['type']       = $row['types'];
            $record['brand']      = $row['brand'];
            $record['model']      = $row['model'];
            $record['capacity']   = $row['capacity'];
            $record['status']     = $row['status'];

            $product = ProductMasterList::where('product_id', $record['product_id'])->first();

            if (!$product) {
                $this->store($record);
            }

            if ($product) {
                if ($record['status'] == ProductMasterList::STATUS_BUY) {
                    $product->quantity += 1;
                } elseif ($record['status'] == ProductMasterList::STATUS_SOLD) {
                    $product->quantity -= 1;
                }

                $product->save();
            }
        }

        return response()->json([
            'message' => 'Product data updated successfully',
        ]);
    }

    public function store($data)
    {
        if ($data['status'] == ProductMasterList::STATUS_BUY) {
            ProductMasterList::create([
                'product_id' => $data['product_id'],
                'type'       => $data['type'],
                'brand'      => $data['brand'],
                'model'      => $data['model'],
                'capacity'   => $data['capacity'],
                'quantity'   => 1,
            ]);
        }
    }
}
