<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentAbsentNotification extends Notification
{
    private $student;
    private $date;

    public function __construct($student, $date)
    {
        $this->student = $student;
        $this->date = $date;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Attendance Alert')
            ->line("Your child {$this->student->name} was absent on {$this->date}.")
            ->line('Please contact the school.');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "{$this->student->name} was absent on {$this->date}",
            'student_id' => $this->student->id,
        ];
    }
}
