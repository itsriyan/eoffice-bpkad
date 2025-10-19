@section('content_top_nav_right')
    <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#" id="notif-bell" role="button" aria-haspopup="true"
            aria-expanded="false">
            <i class="fas fa-bell"></i>
            <span class="badge badge-warning navbar-badge" id="notif-label">0</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" id="notif-dropdown">
            <span class="dropdown-item dropdown-header">Tidak ada notifikasi baru</span>
        </div>
    </li>
@endsection

@push('js')
    <script>
        $('#notif-bell').on('click', function(e) {
            e.preventDefault();
        });

        function loadNotifications() {
            fetch('/api/notifications')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('notif-label').textContent = data.count;
                    let dropdown = document.getElementById('notif-dropdown');
                    if (data.items.length === 0) {
                        dropdown.innerHTML =
                            '<span class="dropdown-item dropdown-header">Tidak ada notifikasi baru</span>';
                    } else {
                        dropdown.innerHTML = '';
                        data.items.forEach(function(item) {
                            dropdown.innerHTML += `
                        <div class="dropdown-item d-flex justify-content-between align-items-center">
                            <a href="${item.url}"><i class="${item.icon} mr-2"></i> ${item.text}</a>
                            <button class="btn btn-sm btn-link tandai-baca" data-id="${item.id}">Tandai Sudah Dibaca</button>
                        </div>
                    `;
                        });
                    }
                });
        }

        // Polling setiap 10 detik
        setInterval(loadNotifications, 10000);
        loadNotifications();

        // Event delegation untuk tombol tandai sudah dibaca
        $(document).on('click', '.tandai-baca', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            fetch('/api/notifications/read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadNotifications();
                    }
                });
        });
    </script>
@endpush
