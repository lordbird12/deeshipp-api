<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale_page_content extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sale_page_contents';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];


    public function html_id()
    {
        return $this->belongsTo(Sale_page::class);
    }

}
