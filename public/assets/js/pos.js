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
  const posCurrency = ['USD', 'CDF'].includes(root.dataset.posCurrency) ? root.dataset.posCurrency : 'USD';
  const exchangeRateValue = Number(root.dataset.posExchangeRate || 2800);
  const exchangeRate = exchangeRateValue > 0 ? exchangeRateValue : 2800;
  const money = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: posCurrency, currencyDisplay: 'code' });
  const currencyFormatters = {
    USD: new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD', currencyDisplay: 'code' }),
    CDF: new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'CDF', currencyDisplay: 'code' }),
  };
  const displayAmount = (usdAmount) => (posCurrency === 'CDF' ? usdAmount * exchangeRate : usdAmount);
  const amountToUsd = (amount, currency) => (currency === 'CDF' ? amount / exchangeRate : amount);
  const usdToAmount = (usdAmount, currency) => (currency === 'CDF' ? usdAmount * exchangeRate : usdAmount);
  const inputAmountToUsd = (amount) => amountToUsd(amount, receivedCurrencySelect?.value || posCurrency);
  const formatMoney = (usdAmount) => money.format(displayAmount(Number(usdAmount || 0)));
  const formatCurrency = (amount, currency) => (currencyFormatters[currency] || currencyFormatters.USD).format(Number(amount || 0));
  const formatEnteredAmount = (amount, currency) => {
    const value = Number(amount || 0);

    if (currency === 'CDF') {
      return `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value)} CDF`;
    }

    return formatCurrency(value, 'USD');
  };
  const formatEnteredPair = (amount, currency, usdAmount) => {
    const enteredCurrency = ['USD', 'CDF'].includes(currency) ? currency : 'USD';
    const enteredAmount = Number(amount || 0);
    const usd = Number(usdAmount || 0);

    if (enteredCurrency === 'CDF') {
      return {
        primary: formatEnteredAmount(enteredAmount, 'CDF'),
        secondary: formatCurrency(usd, 'USD'),
      };
    }

    return {
      primary: formatCurrency(enteredAmount, 'USD'),
      secondary: formatEnteredAmount(enteredAmount * exchangeRate, 'CDF'),
    };
  };
  const formatMoneyDual = (usdAmount) => {
    const amount = Number(usdAmount || 0);
    const primaryCurrency = posCurrency;
    const secondaryCurrency = posCurrency === 'CDF' ? 'USD' : 'CDF';

    return {
      primary: formatCurrency(usdToAmount(amount, primaryCurrency), primaryCurrency),
      secondary: formatCurrency(usdToAmount(amount, secondaryCurrency), secondaryCurrency),
    };
  };
  const formatReceivedCurrency = (usdAmount, forcedCurrency = null) => {
    const currency = forcedCurrency || receivedCurrencySelect?.value || posCurrency;

    return formatCurrency(usdToAmount(Number(usdAmount || 0), currency), currency);
  };
  const formatCartUnit = (item) => formatEnteredPair(item.priceEntered, item.priceCurrency, item.price);
  const formatCartLine = (item) => formatEnteredPair(Number(item.priceEntered || 0) * item.quantity, item.priceCurrency, item.price * item.quantity);
  const formatCartTotal = () => {
    const items = [...cart.values()];
    const currencies = new Set(items.map((item) => item.priceCurrency));

    if (items.length > 0 && currencies.size === 1) {
      const currency = items[0].priceCurrency;
      const enteredTotal = items.reduce((sum, item) => sum + Number(item.priceEntered || 0) * item.quantity, 0);

      return formatEnteredPair(enteredTotal, currency, total());
    }

    return formatMoneyDual(total());
  };
  const cartTotalInCurrency = (currency) => {
    const items = [...cart.values()];
    const normalizedCurrency = ['USD', 'CDF'].includes(currency) ? currency : posCurrency;

    if (items.length > 0 && items.every((item) => item.priceCurrency === normalizedCurrency)) {
      return items.reduce((sum, item) => sum + Number(item.priceEntered || 0) * item.quantity, 0);
    }

    return usdToAmount(total(), normalizedCurrency);
  };
  const formatSaleTotal = (sale) => {
    const entered = Number(sale.total_montant_saisi || 0);
    const currency = ['USD', 'CDF'].includes(sale.devise_saisie) ? sale.devise_saisie : 'USD';

    return entered > 0 ? formatEnteredPair(entered, currency, Number(sale.total_montant || 0)) : formatMoneyDual(Number(sale.total_montant || 0));
  };
  const products = [...root.querySelectorAll('[data-pos-product]')];
  const cartTarget = root.querySelector('[data-pos-cart]');
  const emptyTarget = root.querySelector('[data-pos-empty]');
  const totalTarget = root.querySelector('[data-pos-total]');
  const totalSecondaryTarget = root.querySelector('[data-pos-total-secondary]');
  const countTarget = root.querySelector('[data-pos-count]');
  const changeTarget = root.querySelector('[data-pos-change]');
  const receivedInput = root.querySelector('[data-pos-received]');
  const receivedCurrencySelect = root.querySelector('[data-pos-received-currency]');
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

  const showExpiredProductPopup = (productName, expirationDate) => {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4';

    const panel = document.createElement('div');
    panel.className = 'w-full max-w-md rounded-xl bg-white p-5 shadow-2xl';
    panel.innerHTML = `
      <h3 class="text-lg font-bold text-slate-950">Produit expiré</h3>
      <p class="mt-2 text-sm leading-6 text-slate-600"></p>
      <button class="btn-primary mt-5 h-11 w-full" type="button">Compris</button>
    `;

    const dateText = expirationDate ? ` Date d'expiration: ${expirationDate}.` : '';
    panel.querySelector('p').textContent = `Impossible de vendre ce produit car il a déjà expiré: ${productName || 'Produit'}.${dateText}`;
    const close = () => overlay.remove();
    panel.querySelector('button').addEventListener('click', close);
    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) close();
    });
    document.body.appendChild(overlay);
    panel.querySelector('button').focus();
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
      debt.textContent = formatMoney(Number(customer.dette_actuelle || 0));

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
        throw new Error(data.message || "Impossible d'ajouter ce client.");
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
    const receivedCurrency = receivedCurrencySelect?.value || posCurrency;
    const enteredReceived = Number(receivedInput?.value || 0);
    const received = amountToUsd(enteredReceived, receivedCurrency);

    return {
      customer_id: selectedCustomer ? Number(selectedCustomer.id) : null,
      payment_method: paymentMethod,
      amount_received: received,
      amount_received_entered: enteredReceived,
      received_currency: receivedCurrency,
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
    const receivedCurrency = payload.received_currency || posCurrency;
    const enteredReceived = Number(payload.amount_received_entered || 0);
    const displayDiff = enteredReceived - cartTotalInCurrency(receivedCurrency);

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
        const unit = formatCartUnit(item);
        name.querySelectorAll('p')[1].textContent = `${item.ref || '-'} - ${unit.primary} (${unit.secondary})`;

        const quantity = document.createElement('td');
        quantity.className = 'px-3 py-3 text-right font-semibold';
        quantity.textContent = String(item.quantity);

        const lineTotal = document.createElement('td');
        lineTotal.className = 'px-3 py-3 text-right font-bold text-slate-950';
        const lineTotalAmounts = formatCartLine(item);
        lineTotal.textContent = `${lineTotalAmounts.primary} (${lineTotalAmounts.secondary})`;

        row.append(name, quantity, lineTotal);
        confirmItems.appendChild(row);
      });
    }

    if (confirmTotal) {
      const cartTotal = formatCartTotal();
      confirmTotal.textContent = `${cartTotal.primary} (${cartTotal.secondary})`;
    }
    if (confirmReceived) {
      confirmReceived.textContent = receivedCurrency === posCurrency
        ? formatCurrency(enteredReceived, receivedCurrency)
        : `${formatCurrency(enteredReceived, receivedCurrency)} (${formatMoney(received)})`;
    }
    if (confirmChange) {
      const diffLabel = displayDiff >= 0 ? 'Monnaie à rendre' : 'Reste à payer';
      const equivalent = receivedCurrency === posCurrency ? '' : ` (${formatMoney(Math.abs(diff))})`;
      confirmChange.textContent = `${diffLabel}: ${formatEnteredAmount(Math.abs(displayDiff), receivedCurrency)}${equivalent}`;
      confirmChange.classList.toggle('text-red-700', displayDiff < 0);
      confirmChange.classList.toggle('text-teal-700', displayDiff >= 0);
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

    const payloadToSubmit = {
      ...payload,
      amount_received: Math.min(Number(payload.amount_received || 0), Number(payload.total_amount || 0)),
    };

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
        body: JSON.stringify(payloadToSubmit),
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
      const saleTotal = formatSaleTotal(sale);
      amount.textContent = saleTotal.primary;
      const secondary = document.createElement('span');
      secondary.className = 'block text-xs font-semibold text-slate-500';
      secondary.textContent = saleTotal.secondary;
      amount.appendChild(secondary);

      footer.append(count, amount);
      row.append(header, footer);
      latestSalesTarget.appendChild(row);
    });
  };

  const updatePayment = () => {
    const receivedCurrency = receivedCurrencySelect?.value || posCurrency;
    const enteredReceived = Number(receivedInput?.value || 0);
    const received = amountToUsd(enteredReceived, receivedCurrency);
    const diff = received - total();
    const displayDiff = enteredReceived - cartTotalInCurrency(receivedCurrency);

    if (changeTarget) {
      const label = displayDiff >= 0 ? 'Monnaie à rendre' : 'Reste à payer';
      const equivalent = receivedCurrency === posCurrency ? '' : ` (${formatMoney(Math.abs(diff))})`;
      changeTarget.textContent = `${label}: ${formatEnteredAmount(Math.abs(displayDiff), receivedCurrency)}${equivalent}`;
      changeTarget.classList.toggle('text-red-700', displayDiff < 0);
      changeTarget.classList.toggle('text-teal-700', displayDiff >= 0);
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
        <div class="mt-3 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-3">
          <div class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white">
            <button class="px-3 py-2 font-bold" type="button" data-pos-dec>-</button>
            <span class="min-w-8 text-center text-sm font-bold" data-pos-qty></span>
            <button class="px-3 py-2 font-bold" type="button" data-pos-inc>+</button>
          </div>
          <strong class="min-w-0 break-words text-right text-sm text-slate-950" data-pos-line-total></strong>
        </div>
      `;
      row.querySelector('p').textContent = item.name;
      const unit = formatCartUnit(item);
      row.querySelectorAll('p')[1].textContent = `${item.ref} - ${unit.primary} (${unit.secondary})`;
      row.querySelector('[data-pos-qty]').textContent = item.quantity;
      const lineTotal = formatCartLine(item);
      row.querySelector('[data-pos-line-total]').textContent = `${lineTotal.primary} (${lineTotal.secondary})`;
      cartTarget.appendChild(row);
    });

    const itemCount = items.reduce((sum, item) => sum + item.quantity, 0);
    const totalAmounts = formatCartTotal();
    totalTarget.textContent = totalAmounts.primary;
    if (totalSecondaryTarget) {
      totalSecondaryTarget.textContent = totalAmounts.secondary;
    }
    countTarget.textContent = `${itemCount} article${itemCount > 1 ? 's' : ''}`;
    updatePayment();
  };

  const addProduct = (button) => {
    hideMessage();

    const id = button.dataset.id;
    const expirationDate = String(button.dataset.expirationDate || '').slice(0, 10);

    if (expirationDate !== '') {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const expiresAt = new Date(`${expirationDate}T00:00:00`);

      if (!Number.isNaN(expiresAt.getTime()) && expiresAt < today) {
        showExpiredProductPopup(button.dataset.name || 'Produit', expirationDate);
        return;
      }
    }

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
      priceEntered: Number(button.dataset.priceEntered || button.dataset.price || 0),
      priceCurrency: ['USD', 'CDF'].includes(button.dataset.priceCurrency) ? button.dataset.priceCurrency : 'USD',
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
  receivedCurrencySelect?.addEventListener('change', updatePayment);

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
    const receivedCurrency = receivedCurrencySelect?.value || posCurrency;
    const enteredReceived = Number(receivedInput?.value || 0);

    if ((paymentMethod === 'credit' || amountToUsd(enteredReceived, receivedCurrency) < total()) && !selectedCustomer) {
      showMessage('Sélectionnez ou ajoutez un client pour une vente à crédit.');
      customerSearch?.focus();
      renderCustomerResults();
      return;
    }

    const payload = {
      customer_id: selectedCustomer ? Number(selectedCustomer.id) : null,
      payment_method: paymentMethod,
      amount_received: Math.min(amountToUsd(enteredReceived, receivedCurrency), total()),
      amount_received_entered: enteredReceived,
      received_currency: receivedCurrency,
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
