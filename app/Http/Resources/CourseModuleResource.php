<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseModuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'module_title' => $this->module_title,
            'module_description' => $this->module_description,
            'order_index' => $this->order_index,
            'duration' => $this->duration,
            'quizzes' => ModuleQuizResource::collection($this->whenLoaded('quizzes')),
            'assignments' => ModuleAssignmentResource::collection($this->whenLoaded('assignments')),
            'notes' => ModuleNoteResource::collection($this->whenLoaded('notes')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
