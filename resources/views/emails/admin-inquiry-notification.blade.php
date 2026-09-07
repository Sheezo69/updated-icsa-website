<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New ICSA inquiry</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2>New inquiry received from the ICSA website</h2>

    <p><strong>Form:</strong> {{ $formType }}</p>
    <p><strong>Date:</strong> {{ $contactMessage->created_at?->format('F j, Y \a\t g:i A') }}</p>

    <h3>Visitor Details</h3>
    <p><strong>Name:</strong> {{ $contactMessage->name }}</p>
    <p><strong>Email:</strong> {{ $contactMessage->email }}</p>
    <p><strong>Phone:</strong> {{ $contactMessage->phone }}</p>
    <p><strong>Course:</strong> {{ $contactMessage->course_interest ?: 'General inquiry' }}</p>
    @if ($contactMessage->subject)
        <p><strong>Subject:</strong> {{ $contactMessage->subject }}</p>
    @endif

    @if ($contactMessage->message)
        <h3>Message</h3>
        <p style="white-space: pre-line;">{{ $contactMessage->message }}</p>
    @endif
</body>
</html>
