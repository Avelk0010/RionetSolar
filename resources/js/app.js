document.querySelectorAll('[data-category-select]').forEach((select) => {
	const updateTechnicalFields = () => {
		document.querySelectorAll('[data-spec-group]').forEach((group) => {
			const isSelected = group.dataset.specGroup === select.selectedOptions[0]?.dataset.slug;

			group.hidden = !isSelected;
			group.querySelectorAll('input, select').forEach((field) => {
				field.disabled = !isSelected;
			});
		});
	};

	select.addEventListener('change', updateTechnicalFields);
	updateTechnicalFields();
});

document.querySelectorAll('[data-mobile-menu]').forEach((button) => {
	button.addEventListener('click', () => {
		const sidebar = document.querySelector('[data-sidebar]');
		const isOpen = sidebar.dataset.open === 'true';
		const willOpen = !isOpen;

		sidebar.dataset.open = String(willOpen);
		button.setAttribute('aria-expanded', String(willOpen));
		button.setAttribute('aria-label', willOpen ? 'Cerrar menú' : 'Abrir menú');
	});
});
document.querySelectorAll('[data-string-rows-form]').forEach((form) => {
	const strings = form.querySelectorAll('[data-string-rows]');

	const sumOf = (string) => [...string.querySelectorAll('[data-row] input')]
		.reduce((total, input) => total + (parseInt(input.value, 10) || 0), 0);

	const refresh = (string) => {
		const rows = string.querySelectorAll('[data-row]');
		const expected = parseInt(string.dataset.expected, 10);
		const sum = sumOf(string);
		const summary = string.querySelector('[data-row-summary]');

		rows.forEach((row, index) => {
			row.querySelector('[data-row-label]').textContent = `Fila ${index + 1}`;
			row.querySelector('[data-remove-row]').hidden = rows.length === 1;
		});

		summary.textContent = `Asignados ${sum} de ${expected}`;
		summary.classList.toggle('text-[#3e7136]', sum === expected);
		summary.classList.toggle('text-[#a5443a]', sum !== expected);
		string.querySelector('[data-row-error]').hidden = true;
	};

	strings.forEach((string) => {
		string.querySelector('[data-add-row]').addEventListener('click', () => {
			const fragment = string.querySelector('[data-row-template]').content.cloneNode(true);

			string.querySelector('[data-row-list]').appendChild(fragment);
			refresh(string);
			string.querySelectorAll('[data-row] input')[string.querySelectorAll('[data-row]').length - 1].focus();
		});

		string.addEventListener('click', (event) => {
			const remove = event.target.closest('[data-remove-row]');

			if (remove) {
				remove.closest('[data-row]').remove();
				refresh(string);
			}
		});

		string.addEventListener('input', () => refresh(string));
		refresh(string);
	});

	form.addEventListener('submit', (event) => {
		let firstInvalid = null;

		strings.forEach((string) => {
			const expected = parseInt(string.dataset.expected, 10);
			const values = [...string.querySelectorAll('[data-row] input')].map((input) => parseInt(input.value, 10));
			const message = string.querySelector('[data-row-error]');
			let error = null;

			if (values.some((value) => !Number.isInteger(value) || value < 1)) {
				error = 'Cada fila debe tener al menos 1 panel. Completa o quita las filas vacías.';
			} else if (values.reduce((total, value) => total + value, 0) !== expected) {
				error = `Las filas suman ${values.reduce((total, value) => total + value, 0)} paneles y deben sumar ${expected}.`;
			}

			message.textContent = error ?? '';
			message.hidden = error === null;
			firstInvalid ??= error === null ? null : string;
		});

		if (firstInvalid) {
			event.preventDefault();
			firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
	});
});
document.querySelectorAll('[data-quote-form]').forEach((form) => {
	const body = form.querySelector('[data-quote-rows]');
	const template = form.querySelector('[data-quote-row-template]');
	const money = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2 });
	let nextIndex = parseInt(form.dataset.nextIndex, 10) || 0;

	const number = (input) => parseFloat(input.value) || 0;

	const adjustmentOf = (row) => parseInt(row.querySelector('[data-adjustment]')?.value, 10) || 0;

	const refresh = () => {
		let grand = 0;

		form.querySelectorAll('[data-quote-row]').forEach((row, position) => {
			const rawQuantity = (parseInt(row.dataset.baseQuantity, 10) || 0) + adjustmentOf(row);
			const quantity = Math.max(0, rawQuantity);
			const unitInput = row.querySelector('[data-unit-input]');
			const unit = unitInput ? number(unitInput) : parseFloat(row.dataset.unitPrice) || 0;
			const total = quantity * unit * (1 + number(row.querySelector('[data-tax]')) / 100) * (1 - number(row.querySelector('[data-discount]')) / 100);

			row.querySelector('[data-row-number]').textContent = position + 1;
			if (!unitInput) {
				row.querySelector('[data-unit-price-cell]').textContent = money.format(unit);
			}
			row.querySelector('[data-line-total]').textContent = money.format(total);
			row.querySelector('[data-line-total]').classList.toggle('text-[#a5443a]', rawQuantity < 0);
			grand += total;
		});

		form.querySelector('[data-grand-total]').textContent = money.format(grand);
	};

	form.querySelector('[data-add-quote-row]').addEventListener('click', () => {
		const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));

		body.insertAdjacentHTML('beforeend', html);
		refresh();
		body.querySelector('[data-quote-row]:last-child [data-item-select]')?.focus();
	});

	form.addEventListener('click', (event) => {
		const remove = event.target.closest('[data-remove-quote-row]');

		if (remove) {
			remove.closest('[data-quote-row]').remove();
			refresh();
		}
	});

	form.addEventListener('change', (event) => {
		const select = event.target.closest('[data-item-select]');

		if (select) {
			const row = select.closest('[data-quote-row]');

			row.dataset.unitPrice = select.selectedOptions[0]?.dataset.price ?? '0';
			row.dataset.baseQuantity = '0';
			row.querySelector('[data-base-cell]').textContent = '0';
		}

		refresh();
	});

	form.addEventListener('input', refresh);

	form.addEventListener('submit', (event) => {
		const rows = body.querySelectorAll('[data-quote-row]');
		const invalid = [...rows].find((row) => {
			const item = row.querySelector('[data-item-select]');
			const quantity = (parseInt(row.dataset.baseQuantity, 10) || 0) + adjustmentOf(row);

			return (item && item.value === '') || quantity < 0;
		});

		if (invalid) {
			event.preventDefault();
			invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
			window.alert('Selecciona un ítem en cada fila y revisa que ninguna cantidad final sea negativa.');
		}
	});

	refresh();
});
//
