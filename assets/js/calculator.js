jQuery(document).ready(function($) {
    $('#produk_kredit_pilihan').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const interestRate = selectedOption.data('interest-rate');
        const interestType = selectedOption.data('interest-type');
        // Set the hidden fields with the selected interest rate and type
        $('#interest_rate').val(interestRate);
        $('#interest_type').val(interestType);
        // Optional: Log the selected option for debugging
        console.log('Selected Option:', selectedOption);
    });
    
    $('#cs-credit-form').on('submit', function(e) {
        e.preventDefault();

        const data = {
            action: 'cs_calculate_credit',
            nonce: cs_ajax_object.nonce,
            jumlah_pinjaman: $('#jumlah_pinjaman').val(),
            interest_rate: $('#interest_rate').val(),
            jangka_waktu: $('#jangka_waktu').val(),
            interest_type: $('#interest_type').val()
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
                    <div class="detail-item"><span>Nominal (Rp)</span> <span class="fw-bolder text-danger">${details.nominal}</span></div>
                    <div class="detail-item"><span>Jangka Waktu (Bulan)</span> <span class="fw-bolder text-danger">${details.jangka_waktu}</span></div>
                    <div class="detail-item"><span>Suku bunga per Tahun</span> <span class="fw-bolder text-danger">${details.interest_rate}</span></div>
                    <div class="detail-item"><span>Tipe Bunga yang Digunakan</span> <span class="fw-bolder text-danger">${details.interest_type}</span></div>
                `;
                $('#cs-loan-details').html(detailsHTML);
                //  AKHIR BAGIAN BARU

                // Kode untuk membangun tabel angsuran (tetap sama)
                let tableHTML = `
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: red; color: white;">
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