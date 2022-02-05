<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Note;
use App\Models\Emotion;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NoteTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $note = Note::factory()->create();

        $this->assertTrue($note->contact()->exists());
    }

    /** @test */
    public function it_has_one_author()
    {
        $note = Note::factory()->create();

        $this->assertTrue($note->author()->exists());
    }

    /** @test */
    public function it_has_one_emotion()
    {
        $emotion = Emotion::factory()->create();
        $note = Note::factory()->create([
            'emotion_id' => $emotion->id,
        ]);

        $this->assertTrue($note->emotion()->exists());
    }
}
