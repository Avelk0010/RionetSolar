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
//
