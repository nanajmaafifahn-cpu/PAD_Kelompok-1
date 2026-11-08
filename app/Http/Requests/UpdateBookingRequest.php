<?php

namespace App\Http\Requests;

/**
 * Ubah booking: field sama dengan StoreBookingRequest. Hanya pemilik (BookingPolicy::update);
 * status pending dan cek kapasitas/bentrok ditegakkan BookingService::updateBooking.
 */
class UpdateBookingRequest extends StoreBookingRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('booking'));
    }
}
