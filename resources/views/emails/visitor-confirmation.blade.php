<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICSA inquiry confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Hello {{ $contactMessage->name }},</p>

    <p>Thank you for contacting ICSA. We received your inquiry and a member of our team will get back to you soon.</p>

    @if ($contactMessage->course_interest)
        <p><strong>Course:</strong> {{ $contactMessage->course_interest }}</p>
    @endif

    @if ($contactMessage->message)
        <p><strong>Your message:</strong><br>{{ $contactMessage->message }}</p>
    @endif

    <p>Regards,<br>ICSA International</p>
</body>
</html>
