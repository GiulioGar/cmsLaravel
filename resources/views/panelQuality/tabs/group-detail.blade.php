@php
    $activeLabel = function ($active) {
        switch ((int) $active) {
            case 1: return 'Attivo';
            case 8: return 'Bannato/Sospeso';
            case 9: return 'Cancellato';
            default: return 'Non attivo';
        }
    };
    $anyFlag = collect($members)->contains(fn ($m) => $m['flag_ip'] || $m['flag_birth'] || $m['flag_city'] || $m['flag_reg_day']);
@endphp

<div style="font-size:12px;color:oklch(45% 0.02 250);margin-bottom:12px;">
    Città/Nascita/Registrazione in <strong style="color:#b45309;">ambra</strong> = condivisi tra 2+ membri. Negli IP, <strong>stesso colore = stesso IP</strong> usato da membri diversi (passa il mouse per vedere con chi) — grigio = IP usato solo da questo utente.
    @if(!$anyFlag)
        <br><span style="color:oklch(50% 0.02 250);">Nessun segnale incrociato trovato oltre alla similarity delle risposte (nessun IP di prelievo registrato per questi utenti, o dati anagrafici tutti diversi).</span>
    @endif
</div>

<div style="overflow-x:auto;">
<table style="width:100%;border-collapse:collapse;font-size:12px;">
    <thead>
        <tr style="border-bottom:2px solid oklch(92% 0.006 250);background:oklch(98% 0.004 250);">
            <th style="padding:8px 12px;text-align:left;font-weight:600;color:oklch(45% 0.05 250);font-size:11px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">UID</th>
            <th style="padding:8px 12px;text-align:left;font-weight:600;color:oklch(45% 0.05 250);font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Nome</th>
            <th style="padding:8px 12px;text-align:left;font-weight:600;color:oklch(45% 0.05 250);font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Email</th>
            <th style="padding:8px 12px;text-align:left;font-weight:600;color:oklch(45% 0.05 250);font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Città</th>
            <th style="padding:8px 12px;text-align:left;font-weight:600;color:oklch(45% 0.05 250);font-size:11px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">Nascita</th>
            <th style="padding:8px 12px;text-align:left;font-weight:600;color:oklch(45% 0.05 250);font-size:11px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">Registrazione</th>
            <th style="padding:8px 12px;text-align:left;font-weight:600;color:oklch(45% 0.05 250);font-size:11px;text-transform:uppercase;letter-spacing:.04em;">IP prelievi</th>
        </tr>
    </thead>
    <tbody>
    @foreach($members as $i => $m)
    @php
        $isInactive = $m['active'] !== null && (int) $m['active'] !== 1;
        $rowBg = $i % 2 === 0 ? '#fff' : 'oklch(98.5% 0.003 250)';
        $amber = 'background:oklch(93% 0.08 70);color:oklch(38% 0.12 55);font-weight:700;';
        $uidColor = $isInactive ? 'color:#dc2626;font-weight:700;' : 'color:#1a6fc4;';

        $ipList = $m['ips'];
    @endphp
    <tr style="background:{{ $rowBg }};border-bottom:1px solid oklch(93% 0.004 250);">
        <td style="padding:8px 12px;white-space:nowrap;">
            <a href="{{ url('user/' . $m['uid']) }}" target="_blank" style="text-decoration:none;font-family:monospace;font-size:11px;{{ $uidColor }}">{{ $m['uid'] }}</a>
            @if($isInactive)
                <div style="font-size:10px;color:#dc2626;">{{ $activeLabel($m['active']) }}</div>
            @endif
        </td>
        <td style="padding:8px 12px;">{{ $m['name'] ?: '—' }}</td>
        <td style="padding:8px 12px;font-size:11px;color:oklch(45% 0.02 250);">{{ $m['email'] ?: '—' }}</td>
        <td style="padding:8px 12px;{{ $m['flag_city'] ? $amber : '' }}">{{ $m['city'] ?: '—' }}</td>
        <td style="padding:8px 12px;white-space:nowrap;{{ $m['flag_birth'] ? $amber : '' }}">
            {{ $m['birth_date'] ? \Carbon\Carbon::parse($m['birth_date'])->format('d/m/Y') : '—' }}
        </td>
        <td style="padding:8px 12px;white-space:nowrap;{{ $m['flag_reg_day'] ? $amber : '' }}">
            {{ $m['reg_date'] ? \Carbon\Carbon::parse($m['reg_date'])->format('d/m/Y H:i') : '—' }}
        </td>
        <td style="padding:8px 12px;">
            @if(count($ipList) > 0)
                <div style="display:flex;flex-wrap:wrap;gap:3px;max-width:260px;">
                @foreach($ipList as $ipEntry)
                    @php
                        $badgeStyle = $ipEntry['shared']
                            ? 'background:' . $ipEntry['color'] . ';color:#fff;font-weight:700;'
                            : 'background:oklch(94% 0.01 250);color:oklch(45% 0.02 250);';
                        $tooltip = \Carbon\Carbon::parse($ipEntry['first'])->format('d/m/Y H:i');
                        if ($ipEntry['last'] !== $ipEntry['first']) {
                            $tooltip .= ' → ' . \Carbon\Carbon::parse($ipEntry['last'])->format('d/m/Y H:i');
                        }
                        if ($ipEntry['count'] > 1) {
                            $tooltip .= ' (' . $ipEntry['count'] . ' prelievi)';
                        }
                        if ($ipEntry['shared']) {
                            $tooltip .= "\nCondiviso con: " . implode(', ', $ipEntry['partners']);
                        }
                    @endphp
                    <span title="{{ $tooltip }}" style="display:inline-block;padding:1px 6px;border-radius:4px;font-size:10px;font-family:monospace;white-space:nowrap;{{ $badgeStyle }}">{{ $ipEntry['ip'] }}</span>
                @endforeach
                </div>
            @else
                <span style="color:oklch(60% 0.02 250);">— nessun prelievo registrato</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
