@if (is_array($workPackages ?? null) && count($workPackages))
    <div class="section-title">Pembagian Pekerjaan</div>
    <table>
        <thead><tr><th style="width: 16%;">Nomor Paket</th><th>Nama Pekerjaan</th><th>PIC</th><th>Uraian</th><th style="width: 14%;">Status</th></tr></thead>
        <tbody>
            @foreach ($workPackages as $package)
                @php($assignments = (array) ($package['assignments'] ?? []))
                <tr>
                    <td>{{ $package['display_no'] ?? '-' }}</td>
                    <td>{{ $package['job_name'] ?? '-' }}</td>
                    <td>@foreach($assignments as $assignment){{ $assignment['pic_name'] ?? '-' }}@if(!$loop->last), @endif @endforeach</td>
                    <td>@foreach($assignments as $assignment){{ implode('; ', (array) ($assignment['work_descriptions'] ?? [])) }}@if(!$loop->last); @endif @endforeach</td>
                    <td>{{ $package['status'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
