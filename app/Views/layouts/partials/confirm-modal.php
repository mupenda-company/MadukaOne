<div class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm" data-confirm-modal role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
    <div class="confirm-panel" data-confirm-panel tabindex="-1">
        <div class="flex items-start gap-4">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-100">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 9v4m0 4h.01M10.4 4.8 2.8 18a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.6 4.8a1.8 1.8 0 0 0-3.2 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h2 class="text-lg font-semibold tracking-normal text-slate-950" id="confirm-modal-title" data-confirm-title>Confirmation requise</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600" data-confirm-message>Voulez-vous continuer ?</p>
                <p class="mt-3 hidden text-sm font-semibold text-teal-700" data-confirm-status></p>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button class="btn-secondary w-full sm:w-auto" type="button" data-confirm-cancel>Annuler</button>
            <button class="btn-danger w-full sm:w-auto" type="button" data-confirm-accept>Confirmer</button>
        </div>
    </div>
</div>
