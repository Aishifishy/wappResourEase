<?php

namespace App\Services;

use App\Models\LaboratoryReservation;
use App\Models\ComputerLaboratory;
use App\Models\AcademicTerm;
use App\Mail\LaboratoryReservationStatusChanged;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LaboratoryReservationService
{
    protected $conflictService;

    public function __construct(ReservationConflictService $conflictService)
    {
        $this->conflictService = $conflictService;
    }

    /**
     * Create a new laboratory reservation
     *
     * @param ComputerLaboratory $laboratory
     * @param array $validatedData
     * @return array ['success' => bool, 'reservation' => LaboratoryReservation|null, 'message' => string]
     */
    public function createReservation(ComputerLaboratory $laboratory, array $validatedData): array
    {
        // Convert time formats
        $reservationDate = Carbon::parse($validatedData['reservation_date'])->toDateString();
        $startTime = $validatedData['start_time'];
        $endTime = $validatedData['end_time'];
        
        // Check for conflicts using centralized service
        $conflicts = $this->conflictService->checkConflicts($laboratory->id, $reservationDate, $startTime, $endTime);
        
        if ($conflicts['has_conflict']) {
            $errorMessage = 'The selected time conflicts with ';
            switch ($conflicts['conflict_type']) {
                case 'single_reservation':
                    $errorMessage .= 'an existing reservation.';
                    break;
                case 'recurring_reservation':
                    $errorMessage .= 'a recurring reservation.';
                    break;
                case 'class_schedule':
                    $errorMessage .= 'a scheduled class.';
                    break;
                default:
                    $errorMessage .= 'another booking.';
            }
            
            return [
                'success' => false,
                'reservation' => null,
                'message' => $errorMessage
            ];
        }

        // Create the reservation
        $reservation = new LaboratoryReservation([
            'user_id' => Auth::id(),
            'laboratory_id' => $laboratory->id,
            'reservation_date' => $reservationDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'purpose' => $validatedData['purpose'],
            'num_students' => $validatedData['num_students'],
            'course_code' => $validatedData['course_code'] ?? null,
            'subject' => $validatedData['subject'] ?? null,
            'section' => $validatedData['section'] ?? null,
            'status' => LaboratoryReservation::STATUS_PENDING,
        ]);
        
        // Handle recurring reservations if requested
        if (!empty($validatedData['is_recurring'])) {
            $reservation->is_recurring = true;
            $reservation->recurrence_pattern = $validatedData['recurrence_pattern'];
            $reservation->recurrence_end_date = $validatedData['recurrence_end_date'];
        }
        
        $reservation->save();
        
        return [
            'success' => true,
            'reservation' => $reservation,
            'message' => 'Laboratory reservation request submitted successfully. It will be reviewed by the administrator.'
        ];
    }

    /**
     * Cancel a reservation
     *
     * @param LaboratoryReservation $reservation
     * @return array ['success' => bool, 'message' => string]
     */
    public function cancelReservation(LaboratoryReservation $reservation): array
    {
        // Check if user owns the reservation
        if ($reservation->user_id !== Auth::id()) {
            return [
                'success' => false,
                'message' => 'You can only cancel your own reservations.'
            ];
        }

        // Check if reservation can be canceled (only pending reservations)
        if ($reservation->status !== LaboratoryReservation::STATUS_PENDING) {
            return [
                'success' => false,
                'message' => 'Only pending reservations can be canceled.'
            ];
        }

        try {
            $reservation->update([
                'status' => LaboratoryReservation::STATUS_CANCELLED
            ]);

            // Log the cancellation
            Log::info('Reservation canceled', [
                'reservation_id' => $reservation->id,
                'user_id' => Auth::id(),
                'laboratory_id' => $reservation->laboratory_id,
                'date' => $reservation->reservation_date
            ]);

            return [
                'success' => true,
                'message' => 'Reservation canceled successfully.'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to cancel reservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to cancel reservation. Please try again.'
            ];
        }
    }

    /**
     * Get calendar data for reservations
     *
     * @param Request $request
     * @return array
     */
    public function getCalendarData(Request $request): array
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $laboratoryId = $request->get('laboratory_id');

        // Get the first and last day of the requested month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Build query for reservations
        $query = LaboratoryReservation::with(['user', 'laboratory'])
            ->where('reservation_date', '>=', $startDate->toDateString())
            ->where('reservation_date', '<=', $endDate->toDateString())
            ->where('status', '!=', LaboratoryReservation::STATUS_CANCELLED);

        // Filter by laboratory if specified
        if ($laboratoryId) {
            $query->where('laboratory_id', $laboratoryId);
        } else {
            // Only show user's own reservations if no specific lab is selected
            $query->where('user_id', Auth::id());
        }

        $reservations = $query->get();

        // Get all laboratories for filter dropdown
        $laboratories = ComputerLaboratory::where('status', 'active')->get();

        // Format reservations for calendar
        $calendarEvents = $reservations->map(function ($reservation) {
            $statusColors = [
                LaboratoryReservation::STATUS_PENDING => '#FCD34D', // Yellow
                LaboratoryReservation::STATUS_APPROVED => '#10B981', // Green
                LaboratoryReservation::STATUS_REJECTED => '#EF4444', // Red
            ];

            return [
                'id' => $reservation->id,
                'title' => $reservation->laboratory->name . ' - ' . $reservation->purpose,
                'start' => $reservation->reservation_date . 'T' . $reservation->start_time,
                'end' => $reservation->reservation_date . 'T' . $reservation->end_time,
                'backgroundColor' => $statusColors[$reservation->status] ?? '#6B7280',
                'borderColor' => $statusColors[$reservation->status] ?? '#6B7280',
                'extendedProps' => [
                    'reservation_id' => $reservation->id,
                    'laboratory' => $reservation->laboratory->name,
                    'user' => $reservation->user->name,
                    'status' => $reservation->status,
                    'purpose' => $reservation->purpose,
                    'num_students' => $reservation->num_students,
                ]
            ];
        });

        return [
            'events' => $calendarEvents,
            'laboratories' => $laboratories,
            'currentMonth' => $month,
            'currentYear' => $year
        ];
    }

    /**
     * Get user's reservations with pagination and filtering
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserReservations(Request $request)
    {
        $query = LaboratoryReservation::with(['laboratory'])
            ->where('user_id', Auth::id())
            ->orderBy('reservation_date', 'desc')
            ->orderBy('start_time', 'desc');

        // Apply status filter if provided
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Apply date range filter if provided
        if ($request->has('from_date') && $request->from_date) {
            $query->where('reservation_date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->where('reservation_date', '<=', $request->to_date);
        }

        return $query->paginate(10)->withQueryString();
    }

    /**
     * Validate reservation data
     *
     * @param array $data
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'reservation_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'purpose' => 'required|string|max:500',
            'num_students' => 'required|integer|min:1|max:100',
            'course_code' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:100',
            'section' => 'nullable|string|max:20',
            'is_recurring' => 'nullable|boolean',
            'recurrence_pattern' => 'required_if:is_recurring,true|in:daily,weekly,monthly',
            'recurrence_end_date' => 'required_if:is_recurring,true|date|after:reservation_date',
        ];
    }
}
