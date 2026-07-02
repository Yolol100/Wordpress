(function (wp) {
  const { createElement: h, render, createRoot, useEffect, useMemo, useRef, useState } = wp.element;
  const { __, sprintf } = wp.i18n;
  const apiFetch = wp.apiFetch;
  const {
    Button,
    Card,
    CardBody,
    CardHeader,
    CheckboxControl,
    Notice,
    Spinner,
    TabPanel,
    TextControl,
    TextareaControl,
  } = wp.components;

  const cfg = window.ModernImageManagerAdmin || {};
  apiFetch.use(apiFetch.createNonceMiddleware(cfg.restNonce));

  const exportColumns = [
    ['id', __('ID', 'modern-image-manager')],
    ['filename', __('Bestandsnaam', 'modern-image-manager')],
    ['file_path', __('Bestandspad', 'modern-image-manager')],
    ['new_filename', __('Nieuwe bestandsnaam', 'modern-image-manager')],
    ['slug', __('Slug', 'modern-image-manager')],
    ['page', __('Pagina', 'modern-image-manager')],
    ['title', __('Titel', 'modern-image-manager')],
    ['caption', __('Bijschrift', 'modern-image-manager')],
    ['description', __('Beschrijving', 'modern-image-manager')],
    ['alt_text', __('Alt-tag', 'modern-image-manager')],
    ['file_url', __('Bestands-URL', 'modern-image-manager')],
  ];

  function route(path, options = {}) {
    return apiFetch(Object.assign({ path: '/modern-image-manager/v1' + path }, options));
  }

  function screenToTab(screen) {
    const map = {
      'export-media': 'export',
      'import-media': 'import',
      'scan-duplicate-images': 'scan',
      'zip-media': 'zip',
      export: 'export',
      import: 'import',
      scan: 'scan',
      zip: 'zip',
    };
    return map[screen] || 'media';
  }

  function fixedExtension(item) {
    return item && item.file_extension ? String(item.file_extension).replace(/^\./, '').toLowerCase() : '';
  }

  function displayFilename(item) {
    if (!item) return '';
    const base = String(item.filename || '');
    const ext = fixedExtension(item);
    return ext ? base + '.' + ext : base;
  }

  function filenameBase(value) {
    return String(value || '').trim().replace(/[\/\\]/g, '').replace(/\.[^.\s]{1,10}$/, '');
  }

  function ScreenTitle({ title, description, actions, eyebrow }) {
    return h('div', { className: 'mim-screen-title' },
      h('div', null,
        eyebrow ? h('span', { className: 'mim-eyebrow' }, eyebrow) : null,
        h('h2', null, title),
        description ? h('p', null, description) : null
      ),
      actions ? h('div', { className: 'mim-screen-actions' }, actions) : null
    );
  }

  function EmptyState({ title, description }) {
    return h('div', { className: 'mim-empty-state' },
      h('strong', null, title),
      description ? h('p', null, description) : null
    );
  }

  function Workspace({ title, description, eyebrow, children, single = false }) {
    if (single) {
      return h('section', { className: 'mim-workspace mim-workspace--single' },
        h('div', { className: 'mim-workspace-main' }, children)
      );
    }

    return h('section', { className: 'mim-workspace' },
      h('div', { className: 'mim-workspace-intro' },
        h(ScreenTitle, { title, description, eyebrow })
      ),
      h('div', { className: 'mim-workspace-main' }, children)
    );
  }

  function DetailSection({ title, children, className = '' }) {
    return h('section', { className: ('mim-detail-section ' + className).trim() },
      h('h4', null, title),
      children
    );
  }

  function AppNotice({ notice, onRemove }) {
    if (!notice || !notice.text) {
      return null;
    }

    return h(Notice, { status: notice.type || 'info', isDismissible: true, onRemove }, notice.text);
  }

  function ModernSelect({ label, hideLabelFromVision, value, options, onChange, className = '' }) {
    const [open, setOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);
    const root = useRef(null);
    const listId = useRef('mim-select-' + Math.random().toString(36).slice(2));
    const currentIndex = Math.max(0, options.findIndex((option) => option.value === value));
    const current = options[currentIndex] || options[0];
    const activeOption = options[activeIndex] || current;

    useEffect(() => {
      setActiveIndex(currentIndex);
    }, [currentIndex, value]);

    useEffect(() => {
      if (!open) return undefined;

      function closeOnOutside(event) {
        if (root.current && !root.current.contains(event.target)) {
          setOpen(false);
        }
      }

      document.addEventListener('mousedown', closeOnOutside);

      return () => {
        document.removeEventListener('mousedown', closeOnOutside);
      };
    }, [open]);

    function optionId(index) {
      return listId.current + '-option-' + index;
    }

    function selectOption(index) {
      const option = options[index];
      if (!option) return;
      onChange(option.value);
      setActiveIndex(index);
      setOpen(false);
    }

    function moveActive(delta) {
      setOpen(true);
      setActiveIndex((index) => {
        const next = index + delta;
        if (next < 0) return options.length - 1;
        if (next >= options.length) return 0;
        return next;
      });
    }

    function handleKeyDown(event) {
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        moveActive(1);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        moveActive(-1);
      } else if (event.key === 'Home') {
        event.preventDefault();
        setOpen(true);
        setActiveIndex(0);
      } else if (event.key === 'End') {
        event.preventDefault();
        setOpen(true);
        setActiveIndex(options.length - 1);
      } else if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        if (open) {
          selectOption(activeIndex);
        } else {
          setOpen(true);
        }
      } else if (event.key === 'Escape') {
        event.preventDefault();
        setOpen(false);
      }
    }

    return h('div', { className: ('mim-modern-select ' + (open ? 'is-open ' : '') + className).trim(), ref: root },
      label ? h('label', { className: hideLabelFromVision ? 'screen-reader-text' : 'mim-modern-select__label' }, label) : null,
      h('button', {
        type: 'button',
        className: 'mim-modern-select__button',
        'aria-haspopup': 'listbox',
        'aria-controls': listId.current,
        'aria-expanded': open ? 'true' : 'false',
        'aria-activedescendant': open && activeOption ? optionId(activeIndex) : undefined,
        onClick: () => setOpen((value) => !value),
        onKeyDown: handleKeyDown,
      },
        h('span', { className: 'mim-modern-select__value' }, current ? current.label : ''),
        h('span', { className: 'mim-modern-select__chevron', 'aria-hidden': 'true' })
      ),
      open ? h('div', { id: listId.current, className: 'mim-modern-select__menu', role: 'listbox' },
        options.map((option, index) => h('button', {
          id: optionId(index),
          key: option.value,
          type: 'button',
          className: 'mim-modern-select__option' + (option.value === value ? ' is-selected' : '') + (index === activeIndex ? ' is-active' : ''),
          role: 'option',
          'aria-selected': option.value === value ? 'true' : 'false',
          onMouseEnter: () => setActiveIndex(index),
          onClick: () => selectOption(index),
        },
          h('span', null, option.label),
          option.value === value ? h('span', { className: 'mim-modern-select__check', 'aria-hidden': 'true' }, '✓') : null
        ))
      ) : null
    );
  }

  function ModernSearch({ value, onChange, onSubmit, placeholder }) {
    return h('div', { className: 'mim-search-field' },
      h('span', { className: 'dashicons dashicons-search mim-search-field__icon', 'aria-hidden': 'true' }),
      h('input', {
        className: 'mim-search-field__input',
        type: 'search',
        value,
        placeholder,
        'aria-label': placeholder,
        onChange: (event) => onChange(event.target.value || ''),
        onKeyDown: (event) => { if (event.key === 'Enter') onSubmit(); },
      })
    );
  }

  function MediaManager() {
    const [items, setItems] = useState([]);
    const [originalItems, setOriginalItems] = useState([]);
    const [selected, setSelected] = useState([]);
    const [activeId, setActiveId] = useState(null);
    const [filter, setFilter] = useState('all');
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [pages, setPages] = useState(1);
    const [loading, setLoading] = useState(false);
    const [notice, setNotice] = useState(null);

    function load(nextPage = page) {
      setLoading(true);
      route('/media?filter=' + encodeURIComponent(filter) + '&search=' + encodeURIComponent(search) + '&page=' + nextPage + '&per_page=30')
        .then((res) => {
          const nextItems = res.items || [];
          setItems(nextItems);
          setOriginalItems(nextItems);
          setPages(res.pages || 1);
          setPage(res.page || nextPage);
          setSelected([]);
          setActiveId(nextItems.length ? nextItems[0].id : null);
        })
        .catch((err) => setNotice({ type: 'error', text: err.message || cfg.translations.error }))
        .finally(() => setLoading(false));
    }

    useEffect(() => { load(1); }, [filter]);

    function updateItem(id, patch) {
      setItems((rows) => rows.map((row) => row.id === id ? Object.assign({}, row, patch) : row));
    }

    function save(item) {
      if (!item) return;
      setNotice(null);
      route('/media/' + item.id, {
        method: 'POST',
        data: {
          title: item.title,
          caption: item.caption,
          description: item.description,
          alt: item.alt,
          slug: item.slug || '',
          new_filename: filenameBase(item.new_filename || ''),
        },
      })
        .then(() => { setNotice({ type: 'success', text: cfg.translations.saved }); load(page); })
        .catch((err) => setNotice({ type: 'error', text: err.message || cfg.translations.error }));
    }

    function remove(id) {
      if (!window.confirm(cfg.translations.confirmDelete)) return;
      route('/media/' + id, { method: 'DELETE' })
        .then(() => { setNotice({ type: 'success', text: cfg.translations.deleted }); load(page); })
        .catch((err) => setNotice({ type: 'error', text: err.message || cfg.translations.error }));
    }

    function bulkDelete() {
      if (!selected.length) {
        setNotice({ type: 'warning', text: __('Geen items geselecteerd.', 'modern-image-manager') });
        return;
      }
      if (!window.confirm(sprintf(cfg.translations.confirmBulkDelete, selected.length))) return;
      route('/media/bulk-delete', { method: 'POST', data: { ids: selected } })
        .then((res) => { setSelected([]); setNotice({ type: 'success', text: sprintf(__('%d items verwijderd.', 'modern-image-manager'), res.count || 0) }); load(page); })
        .catch((err) => setNotice({ type: 'error', text: err.message || cfg.translations.error }));
    }

    function toggleSelected(id, checked) {
      setSelected((current) => checked ? Array.from(new Set(current.concat(id))) : current.filter((itemId) => itemId !== id));
    }

    const activeItem = items.find((item) => item.id === activeId) || null;
    const originalActiveItem = originalItems.find((item) => item.id === activeId) || null;
    const activeFilename = activeItem ? displayFilename(activeItem) : '';
    const activeExtension = activeItem && fixedExtension(activeItem) ? '.' + fixedExtension(activeItem) : '';
    const hasChanges = !!activeItem && !!originalActiveItem && ['new_filename', 'title', 'alt', 'slug', 'caption', 'description'].some((key) => String(activeItem[key] || '') !== String(originalActiveItem[key] || ''));
    const allChecked = items.length > 0 && items.every((item) => selected.includes(item.id));

    function resetActiveItem() {
      if (!originalActiveItem) return;
      setItems((rows) => rows.map((row) => row.id === activeId ? Object.assign({}, originalActiveItem) : row));
    }

    return h('section', { className: 'mim-media-screen' },
      h(AppNotice, { notice, onRemove: () => setNotice(null) }),
      h(Card, { className: 'mim-library-card' },
        h(CardHeader, { className: 'mim-library-toolbar' },
          h('div', { className: 'mim-toolbar-main' },
            h(ModernSelect, {
              label: __('Filter', 'modern-image-manager'),
              hideLabelFromVision: true,
              value: filter,
              options: [
                { label: __('Alle afbeeldingen', 'modern-image-manager'), value: 'all' },
                { label: __('Gekoppeld', 'modern-image-manager'), value: 'linked' },
                { label: __('Niet gekoppeld', 'modern-image-manager'), value: 'unlinked' },
              ],
              onChange: setFilter,
            }),
            h(ModernSearch, {
              value: search,
              placeholder: __('Zoek afbeeldingen', 'modern-image-manager'),
              onChange: setSearch,
              onSubmit: () => load(1),
            }),
            h(Button, { variant: 'primary', onClick: () => load(1) }, __('Zoeken', 'modern-image-manager'))
          ),
          h('div', { className: 'mim-toolbar-side' },
            h('div', { className: 'mim-toolbar-stats mim-stat-strip' },
              h('span', null, h('strong', null, String(items.length)), __(' geladen', 'modern-image-manager')),
              h('span', null, h('strong', null, String(selected.length)), __(' geselecteerd', 'modern-image-manager'))
            ),
            h('div', { className: 'mim-toolbar-pagination' },
              h(Button, { variant: 'secondary', disabled: page <= 1, onClick: () => load(page - 1) }, __('Vorige', 'modern-image-manager')),
              h('span', { className: 'mim-page-count' }, page + ' / ' + pages),
              h(Button, { variant: 'secondary', disabled: page >= pages, onClick: () => load(page + 1) }, __('Volgende', 'modern-image-manager'))
            ),
            h('div', { className: 'mim-toolbar-actions' },
              h(Button, { variant: 'secondary', isDestructive: true, disabled: !selected.length, onClick: bulkDelete }, __('Bulk verwijderen', 'modern-image-manager'))
            )
          )
        ),
        h(CardBody, null,
          loading ? h('div', { className: 'mim-loading', role: 'status', 'aria-live': 'polite' }, h(Spinner), h('span', null, cfg.translations.loading)) : null,
          h('div', { className: 'mim-library-layout' },
            h('div', { className: 'mim-list-panel' },
              h('div', { className: 'mim-list-head' },
                h('input', {
                  className: 'mim-check-input',
                  type: 'checkbox',
                  'aria-label': __('Alles selecteren', 'modern-image-manager'),
                  checked: !!allChecked,
                  onChange: (event) => setSelected(event.target.checked ? items.map((item) => item.id) : [])
                }),
                h('span', null, __('Afbeelding', 'modern-image-manager'))
              ),
              h('div', { className: 'mim-media-list' },
                items.length ? items.map((item) => h('div', {
                  key: item.id,
                  className: item.id === activeId ? 'mim-media-row is-active' : 'mim-media-row'
                },
                  h('div', { className: 'mim-row-check' },
                    h('input', {
                      className: 'mim-check-input',
                      type: 'checkbox',
                      'aria-label': sprintf(__('Selecteer %s', 'modern-image-manager'), displayFilename(item) || item.id),
                      checked: selected.includes(item.id),
                      onChange: (event) => toggleSelected(item.id, event.target.checked)
                    })
                  ),
                  h('button', {
                    type: 'button',
                    className: 'mim-row-select',
                    'aria-pressed': item.id === activeId,
                    'aria-label': sprintf(__('Bewerk %s', 'modern-image-manager'), displayFilename(item) || item.id),
                    onClick: () => setActiveId(item.id)
                  },
                    h('span', { className: 'mim-row-file' },
                      item.thumb ? h('img', { className: 'mim-thumb', src: item.thumb, alt: '' }) : h('span', { className: 'mim-thumb mim-thumb--empty' }),
                      h('span', { className: 'mim-row-file-copy' },
                        h('strong', { title: displayFilename(item) || '' }, displayFilename(item) || __('Naamloos bestand', 'modern-image-manager')),
                        h('small', null, item.slug || __('Geen slug', 'modern-image-manager')),
                        h('span', { className: item.status === 'linked' ? 'mim-status' : 'mim-status mim-status--muted' }, item.page || __('Niet gekoppeld', 'modern-image-manager'))
                      )
                    )
                  )
                )) : h(EmptyState, { title: __('Geen afbeeldingen gevonden.', 'modern-image-manager'), description: __('Pas je zoekterm of filter aan.', 'modern-image-manager') })
              )
            ),
            h('aside', { className: 'mim-detail-panel' },
              activeItem ? h('div', null,
                h('div', { className: 'mim-detail-heading' },
                  activeItem.thumb ? h('img', { className: 'mim-detail-thumb', src: activeItem.thumb, alt: '' }) : h('span', { className: 'mim-detail-thumb mim-thumb--empty' }),
                  h('div', null,
                    h('h3', null, __('Metadata bewerken', 'modern-image-manager')),
                    h('p', { title: activeFilename }, activeFilename),
                  hasChanges ? h('span', { className: 'mim-unsaved-pill' }, __('Niet opgeslagen wijzigingen', 'modern-image-manager')) : null
                  )
                ),
                h('div', { className: 'mim-detail-form' },
                  h(DetailSection, { title: __('Bestand', 'modern-image-manager') },
                    h('div', { className: 'mim-filename-control' },
                      h(TextControl, { label: __('Nieuwe bestandsnaam zonder extensie', 'modern-image-manager'), help: activeExtension ? sprintf(__('Pas alleen de naam vóór %s aan. De extensie blijft automatisch behouden.', 'modern-image-manager'), activeExtension) : __('Pas alleen de naam aan. De bestaande extensie blijft automatisch behouden.', 'modern-image-manager'), value: activeItem.new_filename || '', placeholder: activeItem.filename || __('Nieuwe naam', 'modern-image-manager'), onChange: (value) => updateItem(activeItem.id, { new_filename: filenameBase(value) }) }),
                      activeExtension ? h('span', { className: 'mim-extension-badge', 'aria-label': sprintf(__('Vaste extensie: %s', 'modern-image-manager'), activeExtension) }, activeExtension) : null
                    ),
                    h(TextControl, { label: __('Slug', 'modern-image-manager'), value: activeItem.slug || '', onChange: (value) => updateItem(activeItem.id, { slug: value }) })
                  ),
                  h(DetailSection, { title: __('SEO metadata', 'modern-image-manager') },
                    h(TextControl, { label: __('Titel', 'modern-image-manager'), value: activeItem.title || '', onChange: (value) => updateItem(activeItem.id, { title: value }) }),
                    h(TextControl, { label: __('Alt-tag', 'modern-image-manager'), value: activeItem.alt || '', onChange: (value) => updateItem(activeItem.id, { alt: value }) }),
                    h(TextControl, { label: __('Bijschrift', 'modern-image-manager'), value: activeItem.caption || '', onChange: (value) => updateItem(activeItem.id, { caption: value }) })
                  ),
                  h(DetailSection, { title: __('Beschrijving', 'modern-image-manager'), className: 'mim-detail-section--description' },
                    h(TextareaControl, { label: __('Beschrijving', 'modern-image-manager'), value: activeItem.description || '', onChange: (value) => updateItem(activeItem.id, { description: value }) })
                  )
                ),
                h('div', { className: 'mim-detail-actions' },
                  h(Button, { variant: 'primary', disabled: !hasChanges, onClick: () => save(activeItem) }, __('Opslaan', 'modern-image-manager')),
                  h(Button, { variant: 'secondary', disabled: !hasChanges, onClick: resetActiveItem }, __('Herstellen', 'modern-image-manager')),
                  h(Button, { variant: 'secondary', isDestructive: true, onClick: () => remove(activeItem.id) }, __('Verwijderen', 'modern-image-manager'))
                )
              ) : h(EmptyState, { title: __('Selecteer een afbeelding.', 'modern-image-manager'), description: __('De metadata verschijnt hier zodra je een item kiest.', 'modern-image-manager') })
            )
          )
        )
      )
    );
  }

  function ExportScreen() {
    const [option, setOption] = useState('all');
    const [selected, setSelected] = useState(exportColumns.map(([key]) => key));

    if (!cfg.canManage) {
      return h(Workspace, { single: true },
        h(Notice, { status: 'warning', isDismissible: false }, cfg.translations.managePermissionRequired)
      );
    }

    function toggle(key, value) {
      setSelected((current) => value ? Array.from(new Set(current.concat(key))) : current.filter((item) => item !== key));
    }

    return h(Workspace, { single: true },
      h(Card, { className: 'mim-action-card mim-export-card' },
        h(CardHeader, null,
          h('div', null,
            h('h3', null, __('Exportinstellingen', 'modern-image-manager')),
            h('p', null, __('Kies welke afbeeldingen en metadata je wilt exporteren.', 'modern-image-manager'))
          )
        ),
        h(CardBody, null,
          h('form', { className: 'mim-form-stack mim-export-settings', method: 'get', action: cfg.adminPostUrl },
            h('input', { type: 'hidden', name: 'action', value: 'mim_export_csv' }),
            h('input', { type: 'hidden', name: 'mim_export_nonce', value: cfg.exportNonce }),
            h('input', { type: 'hidden', name: 'export_option', value: option }),
            selected.map((key) => h('input', { key: 'hidden-' + key, type: 'hidden', name: 'export_columns[]', value: key })),
            h('section', { className: 'mim-setting-panel' },
              h('div', { className: 'mim-setting-panel__head' },
                h('h4', null, __('Exportbereik', 'modern-image-manager')),
                h('p', null, __('Bepaal welke media in de CSV terechtkomen.', 'modern-image-manager'))
              ),
              h(ModernSelect, {
                label: __('Bereik', 'modern-image-manager'),
                hideLabelFromVision: true,
                value: option,
                options: [
                  { label: __('Alle pagina’s', 'modern-image-manager'), value: 'all' },
                  { label: __('Alleen gekoppelde afbeeldingen', 'modern-image-manager'), value: 'linked' },
                  { label: __('Alleen niet gekoppelde afbeeldingen', 'modern-image-manager'), value: 'unlinked' },
                ],
                onChange: setOption,
              })
            ),
            h('fieldset', { className: 'mim-fieldset mim-fieldset--columns' },
              h('legend', null, __('Kolommen', 'modern-image-manager')),
              h('div', { className: 'mim-checkbox-grid' }, exportColumns.map(([key, label]) =>
                h(CheckboxControl, { key, label, checked: selected.includes(key), onChange: (value) => toggle(key, value) })
              ))
            ),
            h('div', { className: 'mim-export-footer' },
              h(Button, { variant: 'primary', type: 'submit' }, __('Exporteer CSV', 'modern-image-manager')),
              h('span', null, sprintf(__('%d kolommen geselecteerd', 'modern-image-manager'), selected.length))
            )
          )
        )
      )
    );
  }

  function ImportScreen() {
    const result = window.ModernImageManagerImportResult || null;
    const [overwrite, setOverwrite] = useState(false);
    const [preview, setPreview] = useState(null);
    const [fileSelected, setFileSelected] = useState(false);
    const [selectedFileName, setSelectedFileName] = useState('');

    if (!cfg.canManage) {
      return h(Workspace, { single: true },
        h(Notice, { status: 'warning', isDismissible: false }, cfg.translations.managePermissionRequired)
      );
    }

    function parseCsvLine(line) {
      const cells = [];
      let cell = '';
      let quoted = false;
      for (let i = 0; i < line.length; i++) {
        const char = line[i];
        const next = line[i + 1];
        if (char === '"' && quoted && next === '"') {
          cell += '"';
          i++;
        } else if (char === '"') {
          quoted = !quoted;
        } else if (char === ',' && !quoted) {
          cells.push(cell.trim());
          cell = '';
        } else {
          cell += char;
        }
      }
      cells.push(cell.trim());
      return cells.filter(Boolean);
    }

    function handlePreview(event) {
      const file = event.target.files && event.target.files[0];
      setPreview(null);
      setFileSelected(!!file);
      setSelectedFileName(file ? file.name : '');
      if (!file) return;
      const reader = new FileReader();
      reader.onload = () => {
        const text = String(reader.result || '');
        const lines = text.split(/\r?\n/).filter((line) => line.trim() !== '');
        const headers = parseCsvLine(lines[0] || '');
        setPreview({ headers: headers.slice(0, 12), rows: Math.max(0, lines.length - 1) });
      };
      reader.readAsText(file);
    }

    return h(Workspace, { single: true },
      result && result.success ? h(Notice, { status: 'success', isDismissible: false }, result.success) : null,
      result && result.errors && result.errors.length ? h(Notice, { status: 'error', isDismissible: false }, result.errors.join(' ')) : null,
      result && result.summary ? h(Notice, { status: 'info', isDismissible: false }, sprintf(__('Geïmporteerd: %d. Overgeslagen: %d.', 'modern-image-manager'), result.summary.imported || 0, result.summary.skipped || 0)) : null,
      result && result.summary && result.summary.skippedRows && result.summary.skippedRows.length ? h('div', { className: 'mim-import-skips' },
        h('strong', null, __('Eerste overgeslagen rijen', 'modern-image-manager')),
        h('ul', null, result.summary.skippedRows.slice(0, 5).map((item) => h('li', { key: item.row }, sprintf(__('Rij %d: %s', 'modern-image-manager'), item.row, item.reason))))
      ) : null,
      h(Card, { className: 'mim-action-card' },
        h(CardHeader, null,
          h('div', null,
            h('h3', null, __('Importbestand', 'modern-image-manager')),
            h('p', null, __('Upload een CSV-bestand en bepaal hoe lege waarden worden verwerkt.', 'modern-image-manager'))
          )
        ),
        h(CardBody, null,
          h('form', { className: 'mim-form-stack', method: 'post', encType: 'multipart/form-data' },
            h('input', { type: 'hidden', name: 'mim_import_nonce', value: cfg.importNonce }),
            h('input', { type: 'hidden', name: 'import_csv', value: '1' }),
            overwrite ? h('input', { type: 'hidden', name: 'overwrite_empty', value: '1' }) : null,
            h('div', { className: 'mim-upload-box' },
              h('p', { className: 'mim-upload-title' }, __('CSV-bestand', 'modern-image-manager')),
              h('input', { id: 'mim-import-file', className: 'mim-file-input', type: 'file', name: 'import_file', accept: '.csv,text/csv', required: true, onChange: handlePreview }),
              h('div', { className: 'mim-file-control' },
                h('label', { className: 'mim-file-button', htmlFor: 'mim-import-file' }, __('Bestand kiezen', 'modern-image-manager')),
                h('span', { className: selectedFileName ? 'mim-file-name' : 'mim-file-name is-empty', title: selectedFileName || __('Geen bestand gekozen', 'modern-image-manager') }, selectedFileName || __('Geen bestand gekozen', 'modern-image-manager'))
              )
            ),
            preview ? h('div', { className: 'mim-preview-box' },
              h('strong', null, __('CSV-voorbeeld', 'modern-image-manager')),
              h('p', null, sprintf(__('Kolommen: %s. Geschatte datarijen: %d.', 'modern-image-manager'), preview.headers.join(', '), preview.rows)),
              h('small', null, __('Dit is een lokale preview. De server valideert opnieuw en slaat rijen met dubbele bestandsnamen zonder ID of Bestandspad over.', 'modern-image-manager'))
            ) : null,
            h(CheckboxControl, { label: __('Lege waarden mogen bestaande data overschrijven', 'modern-image-manager'), checked: overwrite, onChange: setOverwrite }),
            h('div', { className: 'mim-card-footer-actions' },
              h(Button, { variant: 'primary', type: 'submit', disabled: !fileSelected }, __('Importeer CSV', 'modern-image-manager')),
              h('span', null, fileSelected ? __('Bestaande media blijven behouden.', 'modern-image-manager') : cfg.translations.missingFile)
            )
          )
        )
      )
    );
  }

  function ScanScreen() {
    const [running, setRunning] = useState(false);
    const [result, setResult] = useState(null);
    const [notice, setNotice] = useState(null);

    function scan() {
      setRunning(true);
      setNotice(null);
      setResult(null);
      route('/scan-duplicates')
        .then((res) => setResult(res))
        .catch((err) => setNotice({ type: 'error', text: err.message || cfg.translations.error }))
        .finally(() => setRunning(false));
    }

    const total = result ? (result.total || 0) : 0;
    const totalUsages = result && result.items ? result.items.reduce((sum, item) => sum + (item.usageCount || 0), 0) : 0;

    return h(Workspace, { single: true },
      h(AppNotice, { notice, onRemove: () => setNotice(null) }),
      h(Card, { className: 'mim-action-card mim-scan-card' },
        h(CardHeader, null,
          h('div', null,
            h('h3', null, __('Meervoudig gebruik controleren', 'modern-image-manager')),
            h('p', null, __('Dit zoekt geen identieke bestanden, maar afbeeldingen die op meerdere plekken in content of metadata voorkomen.', 'modern-image-manager'))
          )
        ),
        h(CardBody, null,
          h('div', { className: 'mim-scan-action-bar' },
            h('div', { className: 'mim-scan-action-copy' },
              result ? h('span', { className: 'mim-scan-status' }, __('Scan voltooid', 'modern-image-manager')) : null,
              h('strong', null, result ? sprintf(__('%d resultaat gevonden', 'modern-image-manager'), total) : __('Klaar om te scannen', 'modern-image-manager')),
              h('span', null, result ? sprintf(__('%d gebruiksmomenten gevonden', 'modern-image-manager'), totalUsages) : __('Controleer content, ACF, Elementor en uitgelichte afbeeldingen.', 'modern-image-manager'))
            ),
            h('div', { className: 'mim-scan-action-controls' },
              running ? h(Spinner) : null,
              h(Button, { variant: 'primary', isBusy: running, disabled: running, onClick: scan }, running ? __('Scannen...', 'modern-image-manager') : (result ? __('Opnieuw scannen', 'modern-image-manager') : __('Scan starten', 'modern-image-manager')))
            )
          ),
          result ? h('div', { className: 'mim-scan-results' },
            result.items && result.items.length ? h('div', { className: 'mim-scan-summary' },
              h('strong', null, sprintf(__('%d resultaat gevonden', 'modern-image-manager'), total)),
              h('span', null, sprintf(__('%d gebruiksmomenten gevonden', 'modern-image-manager'), totalUsages))
            ) : null,
            result.items && result.items.length ? h('div', { className: 'mim-scan-result-list' }, result.items.map((item, index) => h('article', { key: index, className: 'mim-scan-result-card' },
              h('div', { className: 'mim-scan-result-media' }, item.thumb ? h('img', { src: item.thumb, alt: '' }) : h('span', null, 'M')),
              h('div', { className: 'mim-scan-result-content' },
                h('div', { className: 'mim-scan-result-head' },
                  h('strong', { title: displayFilename(item) || item.id }, displayFilename(item) || item.id),
                  h('span', { className: 'mim-usage-badge' }, sprintf(__('%d keer gebruikt', 'modern-image-manager'), item.usageCount || 0))
                ),
                item.sourceLabels && item.sourceLabels.length ? h('div', { className: 'mim-source-chip-list' }, item.sourceLabels.map((label) => h('span', { className: 'mim-source-chip', key: label }, label))) : null,
                item.posts && item.posts.length ? h('div', { className: 'mim-scan-posts' },
                  h('span', null, __('Gekoppelde content:', 'modern-image-manager')),
                  h('div', null, item.posts.slice(0, 4).map((post) => post.editUrl ? h('a', { href: post.editUrl, key: post.editUrl }, post.title) : h('span', { key: post.title }, post.title)))
                ) : null
              )
            ))) : h(EmptyState, { title: __('Geen meervoudig gebruik gevonden.', 'modern-image-manager'), description: __('De scan heeft geen afbeeldingen gevonden die op meerdere plekken voorkomen.', 'modern-image-manager') })
          ) : null
        )
      )
    );
  }

  function ZipScreen() {
    return h(Workspace, { single: true },
      h(Card, { className: 'mim-action-card mim-zip-card' },
        h(CardHeader, null,
          h('div', null,
            h('h3', null, __('Media-archief', 'modern-image-manager')),
            h('p', null, __('Download alle afbeeldingen uit de mediabibliotheek als één ZIP-bestand.', 'modern-image-manager'))
          )
        ),
        h(CardBody, null,
          h('form', { className: 'mim-form-stack', method: 'post', action: cfg.adminPostUrl },
            h('input', { type: 'hidden', name: 'action', value: 'modern_image_manager_zip_export' }),
            h('input', { type: 'hidden', name: 'mim_zip_export_nonce_field', value: cfg.zipNonce }),
            h('div', { className: 'mim-zip-summary' },
              h('div', null, h('span', null, __('Status', 'modern-image-manager')), h('strong', null, cfg.zipAvailable ? __('ZIP-module beschikbaar', 'modern-image-manager') : __('ZIP-module ontbreekt', 'modern-image-manager'))),
              h('div', null, h('span', null, __('Bereik', 'modern-image-manager')), h('strong', null, __('Alle afbeeldingen', 'modern-image-manager'))),
              h('div', null, h('span', null, __('Let op', 'modern-image-manager')), h('strong', null, __('Grote bibliotheken kunnen langer duren.', 'modern-image-manager')))
            ),
            !cfg.canManage ? h(Notice, { status: 'warning', isDismissible: false }, cfg.translations.managePermissionRequired) : null,
            !cfg.zipAvailable ? h(Notice, { status: 'warning', isDismissible: false }, __('Vraag de host om de PHP ZipArchive-extensie te activeren.', 'modern-image-manager')) : null,
            h('div', { className: 'mim-export-footer' },
              h(Button, { variant: 'primary', type: 'submit', disabled: !cfg.zipAvailable || !cfg.canManage }, __('ZIP downloaden', 'modern-image-manager')),
              h('span', null, cfg.canManage ? (cfg.zipAvailable ? __('Klaar om het media-archief te maken.', 'modern-image-manager') : __('ZIP-export is niet beschikbaar op deze server.', 'modern-image-manager')) : cfg.translations.managePermissionRequired)
            )
          )
        )
      )
    );
  }

  function App({ screen }) {
    const initialTab = screenToTab(screen);
    const tabs = [
      { name: 'media', title: __('Beheren', 'modern-image-manager') },
      { name: 'export', title: __('Exporteren', 'modern-image-manager') },
      { name: 'import', title: __('Importeren', 'modern-image-manager') },
      { name: 'scan', title: __('Gebruiksscan', 'modern-image-manager') },
      { name: 'zip', title: __('Media-zip', 'modern-image-manager') },
    ];

    function renderScreen(tabName) {
      if (tabName === 'export') return h(ExportScreen);
      if (tabName === 'import') return h(ImportScreen);
      if (tabName === 'scan') return h(ScanScreen);
      if (tabName === 'zip') return h(ZipScreen);
      return h(MediaManager);
    }

    return h('div', { className: 'mim-admin-app' },
      h('header', { className: 'mim-app-header' },
        h('div', { className: 'mim-brand' },
          h('span', { className: 'mim-brand-mark', 'aria-hidden': true }, 'M'),
          h('div', null,
            h('h1', null, __('Media beheren', 'modern-image-manager')),
            h('p', null, __('Beheer bestandsnamen, metadata, gebruiksscans en exports vanuit één mediawerkruimte.', 'modern-image-manager'))
          )
        ),
        h('div', { className: 'mim-header-meta' },
          h('span', { className: 'mim-version-pill' }, 'v' + (cfg.version || '1.5.55')),
          h('span', { className: 'mim-health-pill' }, __('Klaar', 'modern-image-manager'))
        )
      ),
      h(TabPanel, { className: 'mim-tabs', activeClass: 'is-active', initialTabName: initialTab, tabs }, (tab) => renderScreen(tab.name))
    );
  }

  const root = document.getElementById('modern-image-manager-admin-root');
  if (root) {
    const screen = root.dataset.screen || cfg.screen || 'media';
    if (typeof createRoot === 'function') {
      createRoot(root).render(h(App, { screen }));
    } else {
      render(h(App, { screen }), root);
    }
  }
})(window.wp);
