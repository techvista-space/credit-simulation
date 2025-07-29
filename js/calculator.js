jQuery(document).ready(function($) {
    $('#cs-credit-form').on('submit', function(e) {
        e.preventDefault();

        const data = {
            action: 'cs_calculate_credit',
            nonce: cs_ajax_object.nonce,
            jumlah_pinjaman: $('#jumlah_pinjaman').val(),
            suku_bunga: $('#suku_bunga').val(),
            jangka_waktu: $('#jangka_waktu').val(),
            tipe_bunga: $('#tipe_bunga').val()
        };

        // Tampilkan loading
        $('#cs-monthly-payment').text('...');
        $('#cs-loan-details').html('Menghitung...');
        $('#cs-amortization-table').html('');
        $('#cs-result-wrapper').show();

        $.post(cs_ajax_object.ajax_url, data, function(response) {
            if (response.success) {
                const result = response.data;
                
                // Tampilkan angsuran bulanan di bagian kiri
                $('#cs-monthly-payment').text(result.angsuran_bulanan);

                //  BAGIAN BARU: Tampilkan detail pinjaman di bagian kanan
                const details = result.info_pinjaman;
                const detailsHTML = `
                    <div class="detail-item"><span>Nominal (Rp)</span><span>${details.nominal}</span></div>
                    <div class="detail-item"><span>Jangka Waktu (Bulan)</span><span>${details.jangka_waktu}</span></div>
                    <div class="detail-item"><span>Suku bunga per Tahun</span><span>${details.suku_bunga}</span></div>
                    <div class="detail-item"><span>Tipe Bunga yang Digunakan</span><span>${details.tipe_bunga}</span></div>
                `;
                $('#cs-loan-details').html(detailsHTML);
                //  AKHIR BAGIAN BARU

                // Kode untuk membangun tabel angsuran (tetap sama)
                let tableHTML = `
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f7941d; color: white;">
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Periode</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Angsuran Bunga</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Angsuran Pokok</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Total Angsuran</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Sisa Pinjaman</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                result.tabel_angsuran.forEach(row => {
                    tableHTML += `
                        <tr>
                            <td style="padding: 8px; border: 1px solid #ddd;">${row.periode}</td>
                            <td style="padding: 8px; border: 1px solid #ddd;">${row.angsuran_bunga}</td>
                            <td style="padding: 8px; border: 1px solid #ddd;">${row.angsuran_pokok}</td>
                            <td style="padding: 8px; border: 1px solid #ddd;">${row.total_angsuran}</td>
                            <td style="padding: 8px; border: 1px solid #ddd;">${row.sisa_pinjaman}</td>
                        </tr>
                    `;
                });
                tableHTML += `</tbody></table>`;
                $('#cs-amortization-table').html(tableHTML);

            } else {
                $('#cs-loan-details').html('');
                $('#cs-amortization-table').html('<p style="color:red;">Error: ' + response.data.message + '</p>');
            }
        });
    });
});