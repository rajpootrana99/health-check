<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>{{ $emailLog->subject }}</title>
<style>
    /* Reset */
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
    body { margin: 0 !important; padding: 0 !important; background-color: #f4f6f8; }

    /* Responsive */
    @media only screen and (max-width: 600px) {
        .wrapper   { width: 100% !important; }
        .container { padding: 20px 16px !important; }
        .header    { padding: 24px 16px !important; }
    }
</style>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8;">

{{-- Outer wrapper --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#f4f6f8;">
<tr>
<td align="center" style="padding: 32px 16px;">

    {{-- Card --}}
    <table class="wrapper" role="presentation" border="0" cellpadding="0" cellspacing="0"
           width="600" style="max-width:600px; background:#ffffff; border-radius:8px;
                              box-shadow:0 1px 4px rgba(0,0,0,0.08);">

        {{-- Header --}}
        <tr>
            <td class="header" style="background:#4338ca; border-radius:8px 8px 0 0;
                                      padding:28px 36px; text-align:left;">
                <p style="margin:0; font-family:Arial,sans-serif; font-size:11px;
                           font-weight:bold; letter-spacing:2px; text-transform:uppercase;
                           color:#a5b4fc; margin-bottom:6px;">
                    Digital Audit Report
                </p>
                <p style="margin:0; font-family:Arial,sans-serif; font-size:18px;
                           font-weight:bold; color:#ffffff; line-height:1.3;">
                    {{ $business->name }}
                </p>
                <p style="margin:4px 0 0; font-family:Arial,sans-serif; font-size:12px;
                           color:#c7d2fe;">
                    {{ $business->business_type }} &bull; {{ $business->location }}
                </p>
            </td>
        </tr>

        {{-- Score banner --}}
        @if($emailLog->report && $emailLog->report->overall_score !== null)
        @php
            $score = $emailLog->report->overall_score;
            $scoreColor = match(true) {
                $score >= 70 => '#16a34a',
                $score >= 45 => '#d97706',
                default      => '#dc2626',
            };
            $scoreLabel = match(true) {
                $score >= 70 => 'Good',
                $score >= 45 => 'Needs Improvement',
                default      => 'Critical Issues Found',
            };
        @endphp
        <tr>
            <td style="background:#f8fafc; border-bottom:1px solid #e2e8f0;
                        padding:16px 36px; text-align:left;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-family:Arial,sans-serif; font-size:32px;
                                   font-weight:bold; color:{{ $scoreColor }}; line-height:1;
                                   padding-right:12px;">
                            {{ $score }}
                        </td>
                        <td style="vertical-align:middle;">
                            <p style="margin:0; font-family:Arial,sans-serif; font-size:13px;
                                       font-weight:bold; color:#1e293b;">
                                /100 &mdash; {{ $scoreLabel }}
                            </p>
                            <p style="margin:2px 0 0; font-family:Arial,sans-serif;
                                       font-size:11px; color:#64748b;">
                                Overall Digital Health Score
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @endif

        {{-- Email body (AI-generated HTML) --}}
        <tr>
            <td class="container" style="padding:28px 36px; font-family:Arial,sans-serif;
                                          font-size:14px; line-height:1.6; color:#334155;">
                {!! $emailLog->body_html !!}
            </td>
        </tr>

        {{-- CTA button block --}}
        <tr>
            <td style="padding:0 36px 28px; text-align:left;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="border-radius:6px; background:#4338ca;">
                            <a href="https://calendly.com/your-link"
                               target="_blank"
                               style="display:inline-block; padding:12px 24px;
                                      font-family:Arial,sans-serif; font-size:14px;
                                      font-weight:bold; color:#ffffff;
                                      text-decoration:none; border-radius:6px;">
                                Book a Free 15-Minute Call &rarr;
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Report attachment note --}}
        @if($emailLog->report && $emailLog->report->pdf_path)
        <tr>
            <td style="padding:0 36px 28px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0"
                       width="100%">
                    <tr>
                        <td style="background:#eff6ff; border:1px solid #bfdbfe;
                                    border-radius:6px; padding:12px 16px;">
                            <p style="margin:0; font-family:Arial,sans-serif; font-size:13px;
                                       color:#1e40af;">
                                <strong>&#128206; Attached:</strong>
                                Your full digital audit report (PDF) is attached to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @endif

        {{-- Divider --}}
        <tr>
            <td style="padding:0 36px;">
                <hr style="border:none; border-top:1px solid #e2e8f0; margin:0;" />
            </td>
        </tr>

        {{-- Footer --}}
        <tr>
            <td style="padding:20px 36px; font-family:Arial,sans-serif; font-size:11px;
                        color:#94a3b8; text-align:left; border-radius:0 0 8px 8px;">
                <p style="margin:0 0 4px;">
                    You're receiving this because your business was included in our
                    digital health audit for {{ $business->business_type }}s in {{ $business->location }}.
                </p>
                <p style="margin:0;">
                    To unsubscribe, reply with "unsubscribe" in the subject line.
                    &bull; Digital Audit Co. &bull; In compliance with CAN-SPAM &amp; GDPR.
                </p>
            </td>
        </tr>

    </table>
    {{-- /Card --}}

</td>
</tr>
</table>
{{-- /Outer wrapper --}}

</body>
</html>
