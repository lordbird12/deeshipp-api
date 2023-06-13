<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale_page extends Model
{
    use HasFactory;
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }
    public function select_product()
    {
        return $this->belongsTo(Item::class);
    }
    public function sale_page_promotion()
     {
         return $this->hasMany(Sale_page_promotion::class,'sale_pages_id','id');
     }

     public function html()
     {
         return $this->belongsTo(Sale_page_content::class);
     }

     public function sale_page_line()
     {
         return $this->hasMany(Sale_page_line::class,'sale_pages_id','id');
     }

}
