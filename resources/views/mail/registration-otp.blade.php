<x-mail::message>
# Verify your email

Hi {{ $name }},

Use this code to continue creating your {{ config('app.name') }} account:

# {{ $code }}

This code expires in {{ $minutes }} minutes. If you did not request it, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
