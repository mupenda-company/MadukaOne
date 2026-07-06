(() => {
  const root = document.querySelector('[data-pos-root]');

  if (!root) {
    return;
  }

  const endpoint = root.dataset.posEndpoint || '/pos/sale';
  const customerEndpoint = root.dataset.posCustomerEndpoint || '/pos/customer';
  const cart = new Map();
  let customers = [];
  let selectedCustomer = null;
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
  const latestSalesTarget = root.querySelector('[data-pos-latest-sales]');
  const customerSearch = root.querySelector('[data-pos-customer-search]');
  const customerIdInput = root.querySelector('[data-pos-customer-id]');
  const customerResults = root.querySelector('[data-pos-customer-results]');
  const customerEmpty = root.querySelector('[data-pos-customer-empty]');
  const customerSelected = root.querySelector('[data-pos-customer-selected]');
  const customerForm = root.querySelector('[data-pos-customer-form]');
  const customerNameInput = root.querySelector('[data-pos-customer-name]');
  const customerPhoneInput = root.querySelector('[data-pos-customer-phone]');
  const customerSaveButton = root.querySelector('[data-pos-customer-save]');
  const confirmModal = root.querySelector('[data-pos-confirm-modal]');
  const confirmPanel = root.querySelector('[data-pos-confirm-panel]');
  const confirmClient = root.querySelector('[data-pos-confirm-client]');
  const confirmPayment = root.querySelector('[data-pos-confirm-payment]');
  const confirmItems = root.querySelector('[data-pos-confirm-items]');
  const confirmTotal = root.querySelector('[data-pos-confirm-total]');
  const confirmReceived = root.querySelector('[data-pos-confirm-received]');
  const confirmChange = root.querySelector('[data-pos-confirm-change]');
  const confirmAccept = root.querySelector('[data-pos-confirm-accept]');
  const confirmCloseButtons = root.querySelectorAll('[data-pos-confirm-close], [data-pos-confirm-cancel]');
  let pendingPayload = null;

  try {
    customers = JSON.parse(root.querySelector('[data-pos-customers-json]')?.textContent || '[]');
  } catch (error) {
    customers = [];
  }

  const total = () => [...cart.values()].reduce((sum, item) => sum + item.price * item.quantity, 0);

  const paymentLabel = (value) => ({
    cash: 'Cash',
    mobile_money: 'Mobile money',
    carte: 'Carte',
    credit: 'Crédit',
  }[value] || 'Cash');

  const formatDate = (value) => {
    const date = new Date(String(value || '').replace(' ', 'T'));

    if (Number.isNaN(date.getTime())) {
      return value || '';
    }

    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date);
  };

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

  const customerLabel = (customer) => {
    const phone = customer.telephone ? ` · ${customer.telephone}` : '';
    return `${customer.nom || 'Client'}${phone}`;
  };

  const closeCustomerPanels = () => {
    customerResults?.classList.add('hidden');
    customerEmpty?.classList.add('hidden');
    customerForm?.classList.add('hidden');
  };

  const selectCustomer = (customer) => {
    selectedCustomer = customer || null;

    if (customerIdInput) {
      customerIdInput.value = selectedCustomer ? String(selectedCustomer.id) : '';
    }

    if (customerSearch && selectedCustomer) {
      customerSearch.value = selectedCustomer.nom || '';
    }

    if (customerSelected) {
      customerSelected.classList.toggle('hidden', !selectedCustomer);
      customerSelected.replaceChildren();

      if (selectedCustomer) {
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between gap-3';

        const text = document.createElement('p');
        text.className = 'min-w-0 truncate text-sm font-semibold text-teal-900';
        text.textContent = customerLabel(selectedCustomer);

        const clear = document.createElement('button');
        clear.className = 'shrink-0 text-xs font-bold text-teal-700 hover:text-teal-900';
        clear.type = 'button';
        clear.textContent = 'Changer';
        clear.addEventListener('click', () => {
          selectedCustomer = null;
          if (customerIdInput) customerIdInput.value = '';
          if (customerSearch) {
            customerSearch.value = '';
            customerSearch.focus();
          }
          customerSelected.classList.add('hidden');
          renderCustomerResults();
        });

        row.append(text, clear);
        customerSelected.appendChild(row);
      }
    }

    closeCustomerPanels();
  };

  const renderCustomerResults = () => {
    if (!customerSearch || !customerResults || !customerEmpty) {
      return;
    }

    const query = customerSearch.value.trim().toLowerCase();

    if (query === '') {
      closeCustomerPanels();
      return;
    }

    const matches = customers
      .filter((customer) => `${customer.nom || ''} ${customer.telephone || ''}`.toLowerCase().includes(query))
      .slice(0, 6);

    customerResults.replaceChildren();
    customerForm?.classList.add('hidden');

    if (matches.length === 0) {
      customerResults.classList.add('hidden');
      customerEmpty.classList.remove('hidden');
      if (customerNameInput) {
        customerNameInput.value = customerSearch.value.trim();
      }
      return;
    }

    customerEmpty.classList.add('hidden');
    customerResults.classList.remove('hidden');

    matches.forEach((customer) => {
      const button = document.createElement('button');
      button.className = 'flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-slate-50';
      button.type = 'button';

      const label = document.createElement('span');
      label.className = 'min-w-0 truncate font-semibold text-slate-800';
      label.textContent = customerLabel(customer);

      const debt = document.createElement('span');
      debt.className = 'shrink-0 text-xs font-bold text-slate-400';
      debt.textContent = money.format(Number(customer.dette_actuelle || 0));

      button.append(label, debt);
      button.addEventListener('click', () => selectCustomer(customer));
      customerResults.appendChild(button);
    });
  };

  const saveCustomer = async () => {
    if (!customerNameInput || !customerSaveButton) {
      return;
    }

    const name = customerNameInput.value.trim();

    if (name === '') {
      showMessage('Le nom du client est obligatoire.');
      return;
    }

    customerSaveButton.disabled = true;
    customerSaveButton.textContent = 'Enregistrement...';

    try {
      const response = await fetch(customerEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          nom: name,
          telephone: customerPhoneInput?.value || null,
        }),
      });
      const data = await response.json();

      if (!response.ok || !data.ok || !data.customer) {
        throw new Error(data.message || 'Impossible d’ajouter ce client.');
      }

      customers = Array.isArray(data.customers) ? data.customers : [...customers, data.customer];
      selectCustomer(data.customer);
      showMessage(data.message || 'Client ajouté avec succès.', 'success');
    } catch (error) {
      showMessage(error.message || 'Erreur pendant la création du client.');
    } finally {
      customerSaveButton.disabled = false;
      customerSaveButton.textContent = 'Enregistrer';
    }
  };

  const buildSalePayload = () => {
    const paymentMethod = root.querySelector('[data-pos-payment]')?.value || 'cash';
    const received = Number(receivedInput?.value || 0);

    return {
      customer_id: selectedCustomer ? Number(selectedCustomer.id) : null,
      payment_method: paymentMethod,
      amount_received: received,
      total_amount: total(),
      items: [...cart.values()].map((item) => ({
        product_id: Number(item.id),
        quantity: item.quantity,
        unit_price: item.price,
      })),
    };
  };

  const closeSaleConfirmation = () => {
    pendingPayload = null;
    confirmModal?.classList.add('hidden');
    confirmModal?.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');

    if (confirmAccept) {
      confirmAccept.disabled = false;
      confirmAccept.textContent = 'Confirmer la vente';
    }
  };

  const openSaleConfirmation = (payload) => {
    pendingPayload = payload;
    const received = Number(payload.amount_received || 0);
    const saleTotal = Number(payload.total_amount || 0);
    const diff = received - saleTotal;

    if (confirmClient) {
      confirmClient.textContent = selectedCustomer ? customerLabel(selectedCustomer) : 'Client comptant';
    }

    if (confirmPayment) {
      confirmPayment.textContent = paymentLabel(payload.payment_method);
    }

    if (confirmItems) {
      confirmItems.replaceChildren();

      [...cart.values()].forEach((item) => {
        const row = document.createElement('tr');

        const name = document.createElement('td');
        name.className = 'px-3 py-3';
        name.innerHTML = '<p class="font-bold text-slate-950"></p><p class="mt-1 text-xs text-slate-500"></p>';
        name.querySelector('p').textContent = item.name;
        name.querySelectorAll('p')[1].textContent = `${item.ref || '-'} · ${money.format(item.price)}`;

        const quantity = document.createElement('td');
        quantity.className = 'px-3 py-3 text-right font-semibold';
        quantity.textContent = String(item.quantity);

        const lineTotal = document.createElement('td');
        lineTotal.className = 'px-3 py-3 text-right font-bold text-slate-950';
        lineTotal.textContent = money.format(item.price * item.quantity);

        row.append(name, quantity, lineTotal);
        confirmItems.appendChild(row);
      });
    }

    if (confirmTotal) confirmTotal.textContent = money.format(saleTotal);
    if (confirmReceived) confirmReceived.textContent = money.format(received);
    if (confirmChange) {
      confirmChange.textContent = money.format(diff);
      confirmChange.classList.toggle('text-red-700', diff < 0);
      confirmChange.classList.toggle('text-teal-700', diff >= 0);
    }

    confirmModal?.classList.remove('hidden');
    confirmModal?.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    window.setTimeout(() => confirmPanel?.focus(), 0);
  };

  const submitSale = async (payload) => {
    if (!payload) {
      return;
    }

    submitButton.disabled = true;
    submitButton.textContent = 'Validation...';

    if (confirmAccept) {
      confirmAccept.disabled = true;
      confirmAccept.textContent = 'Validation...';
    }

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

      closeSaleConfirmation();
      cart.clear();
      receivedInput.value = '';
      selectCustomer(null);
      if (customerSearch) customerSearch.value = '';
      render();
      renderLatestSales(data.latestSales);
      showMessage(data.message || 'Vente validée.', 'success');
    } catch (error) {
      closeSaleConfirmation();
      showMessage(error.message || 'Erreur de communication avec le serveur.');
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = 'Valider la vente';
    }
  };

  const renderLatestSales = (sales) => {
    if (!latestSalesTarget || !Array.isArray(sales)) {
      return;
    }

    latestSalesTarget.replaceChildren();

    if (sales.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-500';
      empty.dataset.posLatestEmpty = '';
      empty.textContent = 'Aucune vente validée pour le moment.';
      latestSalesTarget.appendChild(empty);
      return;
    }

    sales.slice(0, 10).forEach((sale) => {
      const row = document.createElement('div');
      row.className = 'rounded-lg border border-slate-200 bg-slate-50 p-3';
      row.dataset.posLatestRow = '';

      const header = document.createElement('div');
      header.className = 'flex items-start justify-between gap-3';

      const info = document.createElement('div');
      info.className = 'min-w-0';

      const invoice = document.createElement('p');
      invoice.className = 'truncate text-sm font-bold text-slate-950';
      invoice.textContent = sale.numero_facture || '-';

      const meta = document.createElement('p');
      meta.className = 'mt-1 truncate text-xs text-slate-500';
      meta.textContent = `${sale.customer_name || 'Client comptant'} · ${formatDate(sale.date_vente)}`;

      const badge = document.createElement('span');
      badge.className = 'shrink-0 rounded-full bg-teal-50 px-2 py-1 text-xs font-bold text-teal-700';
      badge.textContent = 'Validée';

      info.append(invoice, meta);
      header.append(info, badge);

      const footer = document.createElement('div');
      footer.className = 'mt-3 flex items-center justify-between gap-3 text-xs text-slate-500';

      const count = document.createElement('span');
      count.textContent = `${Number(sale.articles_count || 0)} article(s)`;

      const amount = document.createElement('strong');
      amount.className = 'text-sm text-slate-950';
      amount.textContent = money.format(Number(sale.total_montant || 0));

      footer.append(count, amount);
      row.append(header, footer);
      latestSalesTarget.appendChild(row);
    });
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

  customerSearch?.addEventListener('input', () => {
    selectedCustomer = null;
    if (customerIdInput) customerIdInput.value = '';
    customerSelected?.classList.add('hidden');
    renderCustomerResults();
  });

  customerSearch?.addEventListener('focus', renderCustomerResults);

  root.querySelector('[data-pos-customer-add-toggle]')?.addEventListener('click', () => {
    customerEmpty?.classList.add('hidden');
    customerForm?.classList.remove('hidden');
    customerNameInput?.focus();
  });

  root.querySelector('[data-pos-customer-cancel]')?.addEventListener('click', () => {
    customerForm?.classList.add('hidden');
    renderCustomerResults();
  });

  customerSaveButton?.addEventListener('click', saveCustomer);

  document.addEventListener('click', (event) => {
    if (!root.contains(event.target)) {
      return;
    }

    const isCustomerControl = event.target.closest('[data-pos-customer-search], [data-pos-customer-results], [data-pos-customer-empty], [data-pos-customer-form], [data-pos-customer-selected]');
    if (!isCustomerControl) {
      customerResults?.classList.add('hidden');
      customerEmpty?.classList.add('hidden');
    }
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

  confirmCloseButtons.forEach((button) => button.addEventListener('click', closeSaleConfirmation));

  confirmModal?.addEventListener('click', (event) => {
    if (event.target === confirmModal) {
      closeSaleConfirmation();
    }
  });

  confirmAccept?.addEventListener('click', () => submitSale(pendingPayload));

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && confirmModal && !confirmModal.classList.contains('hidden')) {
      closeSaleConfirmation();
    }
  });

  submitButton?.addEventListener('click', (event) => {
    event.stopImmediatePropagation();
    hideMessage();

    if (cart.size === 0) {
      showMessage('Ajoutez au moins un article avant de valider la vente.');
      return;
    }

    const payload = buildSalePayload();

    if ((payload.payment_method === 'credit' || Number(payload.amount_received || 0) < total()) && !selectedCustomer) {
      showMessage('Sélectionnez ou ajoutez un client pour une vente à crédit.');
      customerSearch?.focus();
      renderCustomerResults();
      return;
    }

    openSaleConfirmation(payload);
  });

  submitButton?.addEventListener('click', async () => {
    hideMessage();

    if (cart.size === 0) {
      showMessage('Ajoutez au moins un article avant de valider la vente.');
      return;
    }

    const paymentMethod = root.querySelector('[data-pos-payment]')?.value || 'cash';

    if ((paymentMethod === 'credit' || Number(receivedInput?.value || 0) < total()) && !selectedCustomer) {
      showMessage('Sélectionnez ou ajoutez un client pour une vente à crédit.');
      customerSearch?.focus();
      renderCustomerResults();
      return;
    }

    const payload = {
      customer_id: selectedCustomer ? Number(selectedCustomer.id) : null,
      payment_method: paymentMethod,
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
      selectCustomer(null);
      if (customerSearch) customerSearch.value = '';
      render();
      renderLatestSales(data.latestSales);
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
