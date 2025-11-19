<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'transaction_id' => $this->id,
            'member' => $this->whenLoaded('user', function () {
                return $this->user->name;
            }),
            'book_details' => $this->whenLoaded('book', function () {
                return [
                    'title' => $this->book->title,
                    'author' => $this->book->author,
                ];
            }),
            'borrow_date' => $this->borrow_date,
            'due_date' => $this->due_date,
            'return_date' => $this->return_date,
            'status' => $this->status,
            'fine' => $this->whenLoaded('fine', function () {
                return $this->fine ? new FineResource($this->fine) : null;
            }),
        ];
    }
}
