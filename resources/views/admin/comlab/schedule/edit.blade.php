@extends('layouts.admin')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">Edit Schedule</h1>
        <a href="{{ route('admin.comlab.calendar') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Calendar
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calendar-edit me-1"></i>
            Edit Schedule for {{ $laboratory->name }} ({{ $laboratory->building }} - {{ $laboratory->room_number }})
        </div>
        <div class="card-body">
            <form action="{{ route('admin.comlab.schedule.update', [$laboratory, $schedule]) }}" method="POST">
                @csrf
                @method('PUT')
                  <x-form-error field="time_conflict" />

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="subject_code" class="form-label">Subject Code</label>                            <input type="text" class="form-control @error('subject_code') is-invalid @enderror" 
                                id="subject_code" name="subject_code" 
                                value="{{ old('subject_code', $schedule->subject_code) }}" maxlength="20">
                            <x-form-error field="subject_code" />
                        </div>

                        <div class="mb-3">
                            <label for="subject_name" class="form-label">Subject Name</label>                            <input type="text" class="form-control @error('subject_name') is-invalid @enderror" 
                                id="subject_name" name="subject_name" 
                                value="{{ old('subject_name', $schedule->subject_name) }}" required maxlength="100">
                            <x-form-error field="subject_name" />
                        </div>

                        <div class="mb-3">
                            <label for="instructor_name" class="form-label">Instructor Name</label>                            <input type="text" class="form-control @error('instructor_name') is-invalid @enderror" 
                                id="instructor_name" name="instructor_name" 
                                value="{{ old('instructor_name', $schedule->instructor_name) }}" required maxlength="100">
                            <x-form-error field="instructor_name" />
                        </div>

                        <div class="mb-3">
                            <label for="section" class="form-label">Section</label>                            <input type="text" class="form-control @error('section') is-invalid @enderror" 
                                id="section" name="section" 
                                value="{{ old('section', $schedule->section) }}" required maxlength="20">
                            <x-form-error field="section" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="day_of_week" class="form-label">Day of Week</label>
                            <select class="form-select @error('day_of_week') is-invalid @enderror" 
                                id="day_of_week" name="day_of_week" required>
                                <option value="">Select Day</option>
                                <option value="0" {{ old('day_of_week', $schedule->day_of_week) === 0 ? 'selected' : '' }}>Sunday</option>
                                <option value="1" {{ old('day_of_week', $schedule->day_of_week) === 1 ? 'selected' : '' }}>Monday</option>
                                <option value="2" {{ old('day_of_week', $schedule->day_of_week) === 2 ? 'selected' : '' }}>Tuesday</option>
                                <option value="3" {{ old('day_of_week', $schedule->day_of_week) === 3 ? 'selected' : '' }}>Wednesday</option>
                                <option value="4" {{ old('day_of_week', $schedule->day_of_week) === 4 ? 'selected' : '' }}>Thursday</option>
                                <option value="5" {{ old('day_of_week', $schedule->day_of_week) === 5 ? 'selected' : '' }}>Friday</option>
                                <option value="6" {{ old('day_of_week', $schedule->day_of_week) === 6 ? 'selected' : '' }}>Saturday</option>                            </select>
                            <x-form-error field="day_of_week" />
                        </div>

                        <div class="mb-3">
                            <label for="start_time" class="form-label">Start Time</label>                            <input type="time" class="form-control @error('start_time') is-invalid @enderror" 
                                id="start_time" name="start_time" 
                                value="{{ old('start_time', $schedule->start_time->format('H:i')) }}" required>
                            <x-form-error field="start_time" />
                        </div>

                        <div class="mb-3">
                            <label for="end_time" class="form-label">End Time</label>                            <input type="time" class="form-control @error('end_time') is-invalid @enderror" 
                                id="end_time" name="end_time" 
                                value="{{ old('end_time', $schedule->end_time->format('H:i')) }}" required>
                            <x-form-error field="end_time" />
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Schedule Type</label>
                            <select class="form-select @error('type') is-invalid @enderror" 
                                id="type" name="type" required>
                                <option value="regular" {{ old('type', $schedule->type) === 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="special" {{ old('type', $schedule->type) === 'special' ? 'selected' : '' }}>Special</option>
                            </select>                            <x-form-error field="type" />
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                        id="notes" name="notes" rows="3">{{ old('notes', $schedule->notes) }}</textarea>                    <x-form-error field="notes" />
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection