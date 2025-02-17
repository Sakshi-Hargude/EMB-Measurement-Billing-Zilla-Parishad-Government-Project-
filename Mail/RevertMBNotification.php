<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RevertMBNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $status;
    public $workData;
    public $Userdetail;
    public $tbillData;
    public $From;

    /**
     * Create a new message instance.
     *
     * @param mixed $tbillId
     * @param mixed $workData
     * @param mixed $from
     */
    public function __construct($status, $workdata , $billdata , $from , $userdetails)
    {
        $this->status = $status;
        $this->workData = $workdata;
        $this->Userdetail = $userdetails;
        $this->tbillData = $billdata;
        $this->From = $from;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Measurement Book Status Reverted',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.revert_mb_notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments(): array
    {
        return [];
    }
}
