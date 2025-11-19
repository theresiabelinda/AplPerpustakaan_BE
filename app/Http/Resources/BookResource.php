<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $availableStock = $this->stock;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'publisher' => $this->publisher,
            'category_name' => $this->category->name ?? 'N/A',
            'stock_available' => $availableStock,
            'shelf_location' => $this->shelf_location,
            'is_available' => $availableStock > 0,
            'published_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
