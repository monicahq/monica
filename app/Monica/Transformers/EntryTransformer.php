<?php
namespace Monica\Transformers;

use League\Fractal;
use League\Fractal\TransformerAbstract;
use App\Entry;

class EntryTransformer extends TransformerAbstract
{
    public function transform(Entry $entry) {

        return [
            'id' => $entry->id,
            'title' => $entry->title,
            'post' => $entry->post
        ];
    }
}

?>
