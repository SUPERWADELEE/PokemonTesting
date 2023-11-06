@component('mail::message')
# Email Verification

Thank you for registering with our application. Please click the button below to verify your email address.

@component('mail::button', ['url' => route('verification.verify', ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())])])
Verify Email
@endcomponent

If you did not create an account, no further action is required.

Regards,<br>
Pokemons Manager
@endcomponent
