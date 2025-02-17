<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MBStatusUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $status;
    public $tBillNo;
    public $billType;
    public $workData;
    public $Userdetail;
    public $tbillData;
    public $From;
    /**
     * Create a new message instance.
     *
     * @param int $status
     * @param int $tBillId
     * @param int $workId
     */
    public function __construct($status, $tBillNo, $billType, $workdata , $billdata , $from , $userdetails)
    {
        $this->status = $status;
        $this->tBillNo = $tBillNo;
        $this->billType = $billType;
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
        $subject = $this->status > 6 ? 'Checking Of Bill' : 'Measurement Book Status Updated';

        return new Envelope(
            subject: $subject
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.mb_status_updated',
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
