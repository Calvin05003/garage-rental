<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentService
{
    public function recordPayment(Rental $rental, User $uploader, string $amount, UploadedFile $proof): Payment
    {
        $path = $proof->store('payments', 'local');

        if ($path === false) {
            throw ValidationException::withMessages([
                'payment_receipt' => 'The receipt could not be saved. Please try again.',
            ]);
        }

        try {
            return $rental->payments()->create([
                'uploaded_by' => $uploader->user_id,
                'proof_path' => $path,
                'amount' => $amount,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
    }
}
