<x-mail::message>
# Verify your email

Hi {{ $name }},

Use this code to continue creating your {{ config('marketing.brand', config('app.name')) }} account:

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

This code expires in {{ $minutes }} minutes. If you did not request it, you can ignore this email.

Thanks,<br>
{{ config('marketing.brand', config('app.name')) }}
</x-mail::message>
