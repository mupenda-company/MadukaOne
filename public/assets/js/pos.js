(() => {
  const root = document.querySelector('[data-pos-root]');

  if (!root) {
    return;
  }

  const endpoint = root.dataset.posEndpoint || '/pos/sale';
  const cart = new Map();
  const money = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' });
  const products = [...root.querySelectorAll('[data-pos-product]')];
  const cartTarget = root.querySelector('[data-pos-cart]');
  const emptyTarget = root.querySelector('[data-pos-empty]');
  const totalTarget = root.querySelector('[data-pos-total]');
  const countTarget = root.querySelector('[data-pos-count]');
  const changeTarget = root.querySelector('[data-pos-change]');
  const receivedInput = root.querySelector('[data-pos-received]');
  const messageTarget = root.querySelector('[data-pos-message]');
  const submitButton = root.querySelector('[data-pos-submit]');

  const total = () => [...cart.values()].reduce((sum, item) => sum + item.price * item.quantity, 0);

  const showMessage = (message, tone = 'error') => {
    if (!messageTarget) {
      return;
    }

    messageTarget.textContent = message;
    messageTarget.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-teal-50', 'text-teal-700');
    messageTarget.classList.add(tone === 'success' ? 'bg-teal-50' : 'bg-red-50', tone === 'success' ? 'text-teal-700' : 'text-red-700');
  };

  const hideMessage = () => {
    messageTarget?.classList.add('hidden');
  };

  const updatePayment = () => {
    const received = Number(receivedInput?.value || 0);
    const diff = received - total();

    if (changeTarget) {
      changeTarget.textContent = money.format(diff);
      changeTarget.classList.toggle('text-red-700', diff < 0);
      changeTarget.classList.toggle('text-teal-700', diff >= 0);
    }
  };

  const render = () => {
    const items = [...cart.values()];

    cartTarget.querySelectorAll('[data-pos-cart-row]').forEach((row) => row.remove());
    emptyTarget?.classList.toggle('hidden', items.length > 0);

    items.forEach((item) => {
      const row = document.createElement('div');
      row.className = 'rounded-lg border border-slate-200 bg-slate-50 p-3';
      row.dataset.posCartRow = item.id;
      row.innerHTML = `
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="truncate text-sm font-bold text-slate-950"></p>
            <p class="text-xs text-slate-500"></p>
          </div>
          <button class="rounded-lg px-2 py-1 text-xs font-bold text-red-700 hover:bg-red-50" type="button" data-pos-remove>Retirer</button>
        </div>
        <div class="mt-3 flex items-center justify-between gap-3">
          <div class="inline-flex items-center rounded-lg border border-slate-200 bg-white">
            <button class="px-3 py-2 font-bold" type="button" data-pos-dec>-</button>
            <span class="min-w-8 text-center text-sm font-bold" data-pos-qty></span>
            <button class="px-3 py-2 font-bold" type="button" data-pos-inc>+</button>
          </div>
          <strong class="text-sm text-slate-950" data-pos-line-total></strong>
        </div>
      `;
      row.querySelector('p').textContent = item.name;
      row.querySelectorAll('p')[1].textContent = `${item.ref} · ${money.format(item.price)}`;
      row.querySelector('[data-pos-qty]').textContent = item.quantity;
      row.querySelector('[data-pos-line-total]').textContent = money.format(item.price * item.quantity);
      cartTarget.appendChild(row);
    });

    const itemCount = items.reduce((sum, item) => sum + item.quantity, 0);
    totalTarget.textContent = money.format(total());
    countTarget.textContent = `${itemCount} article${itemCount > 1 ? 's' : ''}`;
    updatePayment();
  };

  const addProduct = (button) => {
    hideMessage();

    const id = button.dataset.id;
    const current = cart.get(id);
    const stock = Number(button.dataset.stock || 0);
    const nextQuantity = current ? current.quantity + 1 : 1;

    if (nextQuantity > stock) {
      showMessage('Stock insuffisant pour cet article.');
      return;
    }

    cart.set(id, {
      id,
      name: button.dataset.name || 'Produit',
      ref: button.dataset.ref || '',
      price: Number(button.dataset.price || 0),
      stock,
      quantity: nextQuantity,
    });

    render();
  };

  products.forEach((button) => button.addEventListener('click', () => addProduct(button)));

  root.querySelector('[data-pos-search]')?.addEventListener('input', (event) => {
    const query = event.target.value.toLowerCase().trim();

    products.forEach((button) => {
      const label = `${button.dataset.name || ''} ${button.dataset.ref || ''}`.toLowerCase();
      button.classList.toggle('hidden', query !== '' && !label.includes(query));
    });
  });

  cartTarget?.addEventListener('click', (event) => {
    const row = event.target.closest('[data-pos-cart-row]');

    if (!row) {
      return;
    }

    const item = cart.get(row.dataset.posCartRow);

    if (!item) {
      return;
    }

    if (event.target.matches('[data-pos-inc]')) {
      if (item.quantity >= item.stock) {
        showMessage('Stock insuffisant pour cet article.');
        return;
      }
      item.quantity += 1;
    }

    if (event.target.matches('[data-pos-dec]')) {
      item.quantity -= 1;
      if (item.quantity <= 0) {
        cart.delete(item.id);
      }
    }

    if (event.target.matches('[data-pos-remove]')) {
      cart.delete(item.id);
    }

    render();
  });

  root.querySelector('[data-pos-clear]')?.addEventListener('click', () => {
    cart.clear();
    hideMessage();
    render();
  });

  receivedInput?.addEventListener('input', updatePayment);

  submitButton?.addEventListener('click', async () => {
    hideMessage();

    if (cart.size === 0) {
      showMessage('Ajoutez au moins un article avant de valider la vente.');
      return;
    }

    const payload = {
      customer: root.querySelector('[data-pos-customer]')?.value || null,
      payment_method: root.querySelector('[data-pos-payment]')?.value || 'cash',
      amount_received: Number(receivedInput?.value || 0),
      total_amount: total(),
      items: [...cart.values()].map((item) => ({
        product_id: Number(item.id),
        quantity: item.quantity,
        unit_price: item.price,
      })),
    };

    submitButton.disabled = true;
    submitButton.textContent = 'Validation...';

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
      });
      const data = await response.json();

      if (!response.ok || !data.ok) {
        throw new Error(data.message || 'Impossible de valider la vente.');
      }

      cart.clear();
      receivedInput.value = '';
      render();
      showMessage(data.message || 'Vente validée.', 'success');
    } catch (error) {
      showMessage(error.message || 'Erreur de communication avec le serveur.');
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = 'Valider la vente';
    }
  });

  render();
})();
