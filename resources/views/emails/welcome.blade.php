<x-mail::message>
# Welcome to {{ config('app.name') }}

Hi {{ $user->name ?? 'there' }},

We're excited to have you on board! Your account has been created successfully.

You can now log in and start managing your tasks right away.

<x-mail::button :url="config('app.url')">
Go to {{ config('app.name') }}
</x-mail::button>

If you didn't create this account, please ignore this email.

Thanks,
{{ config('app.name') }} Team
</x-mail::message>
