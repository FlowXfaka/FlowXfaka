@once
    @push('head')
        <style>
        .admin-stock-toast{position:fixed;left:50%;top:50%;z-index:90;display:inline-flex;align-items:center;justify-content:center;min-height:2.85rem;padding:.8rem 1.3rem;border-radius:999rem;background:rgba(17,24,39,.9);color:#fff;font-size:.92rem;font-weight:800;box-shadow:0 .9rem 1.8rem rgba(0,0,0,.24);opacity:0;pointer-events:none;transform:translate(-50%,calc(-50% + .75rem));transition:opacity .18s ease,transform .18s ease}.admin-stock-toast.is-visible{opacity:1;transform:translate(-50%,-50%)}
        </style>
    @endpush

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toast = document.querySelector('[data-stock-shortage-toast]');

            if (!toast) {
                return;
            }

            let timerId = null;

            document.querySelectorAll('[data-stock-shortage-trigger]').forEach((button) => {
                button.addEventListener('click', () => {
                    toast.classList.add('is-visible');
                    clearTimeout(timerId);
                    timerId = setTimeout(() => {
                        toast.classList.remove('is-visible');
                    }, 1600);
                });
            });
        });
        </script>
    @endpush
@endonce

<div class="admin-stock-toast" data-stock-shortage-toast="1">库存不足</div>
