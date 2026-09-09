<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Comunicazione qualità intervista</title>
</head>
<body style="margin:0; padding:0; background:#f4f5f7; font-family: Arial, Helvetica, sans-serif; color:#333;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="background:#dc2626; padding:18px 24px;">
                            <span style="color:#ffffff; font-size:16px; font-weight:bold;">Comunicazione qualità intervista</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 16px; font-size:14px; line-height:1.5;">
                                Ciao {{ $nome }},
                            </p>
                            <p style="margin:0 0 16px; font-size:14px; line-height:1.5;">
                                ti abbiamo assegnato una penalità di <strong>{{ $valore }} bytes</strong> perché
                                abbiamo riscontrato: {{ $motivazione }}
                            </p>
                            <p style="margin:0; font-size:13px; line-height:1.5; color:#6b7280;">
                                Per qualsiasi chiarimento puoi contattare l'assistenza rispondendo a questa email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
