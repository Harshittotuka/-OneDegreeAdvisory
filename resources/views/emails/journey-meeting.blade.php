@extends('emails.layout', [
    'title' => $meeting['title'],
    'eyebrow' => 'Your meeting with One Degree',
    'preheader' => $meeting['title'].' — '.$when,
])

@section('content')
    <div style="font-size:13px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#0f7a78;">{{ $isUpdate ? 'Meeting updated' : 'Meeting scheduled' }}</div>
    <h1 style="margin:10px 0 12px;font-size:27px;line-height:1.25;color:#102a43;">{{ $meeting['title'] }}</h1>
    <p style="margin:0;font-size:15px;line-height:1.75;color:#526674;">
        {{ $isUpdate ? 'The details for this meeting have changed.' : 'This meeting has been set up' }}{{ $isUpdate ? '' : ' for '.$studentName }}{{ $counsellorName ? ' by '.$counsellorName : '' }}.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;border:1px solid #cfe1df;border-radius:12px;background:#f1f8f7;">
        <tr>
            <td style="padding:20px 22px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;color:#102a43;">
                    <tr>
                        <td style="padding:4px 0;width:120px;color:#61757f;font-size:13px;">When</td>
                        <td style="padding:4px 0;font-weight:700;">{{ $when }}{{ $meeting['minutes'] ? ' · '.$meeting['minutes'].' minutes' : '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#61757f;font-size:13px;">How</td>
                        <td style="padding:4px 0;">{{ $meeting['mode'] }}</td>
                    </tr>
                    @if($meeting['who'])
                        <tr>
                            <td style="padding:4px 0;color:#61757f;font-size:13px;">Who</td>
                            <td style="padding:4px 0;">{{ $meeting['who'] }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @if($meeting['mode'] === 'Phone call' && $meeting['phone'])
        <p style="margin:0 0 8px;font-size:13px;color:#61757f;">{{ $joinLabel }}</p>
        <p style="margin:0 0 22px;font-size:22px;font-weight:700;color:#102a43;">
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $meeting['phone']) }}" style="color:#0f5f5d;text-decoration:none;">{{ $meeting['phone'] }}</a>
        </p>
    @elseif($meeting['link'])
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 22px;">
            <tr>
                <td style="border-radius:10px;background:#0f7a78;">
                    <a href="{{ $meeting['link'] }}" style="display:inline-block;padding:14px 28px;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;">Join the meeting</a>
                </td>
            </tr>
        </table>
        <p style="margin:0 0 22px;font-size:13px;line-height:1.7;color:#6b7d87;word-break:break-all;">
            Or paste this into your browser:<br><a href="{{ $meeting['link'] }}" style="color:#0f5f5d;">{{ $meeting['link'] }}</a>
        </p>
    @endif

    @if($meeting['notes'])
        <p style="margin:0 0 6px;font-size:13px;color:#61757f;">Notes</p>
        <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#102a43;white-space:pre-wrap;">{{ $meeting['notes'] }}</p>
    @endif

    <p style="margin:0;font-size:13px;line-height:1.7;color:#6b7d87;">If this time no longer works, reply to this email and we will move it.</p>
@endsection
