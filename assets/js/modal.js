(function() {
    const { createElement, useState, useEffect, useMemo, useRef } = wp.element;
    const { Modal, Button, TextControl, Spinner } = wp.components;
    const { Fragment } = wp.element;
    const { BlockPreview } = wp.blockEditor;
    const { parse } = wp.blocks;

    // Component for individual pattern preview
    const PatternPreviewItem = ({ pattern, onSelect }) => {
        const [blocks, setBlocks] = useState(null);
        const [isFetching, setIsFetching] = useState(false);

        useEffect(() => {
            if (pattern.content) {
                setBlocks(parse(pattern.content));
            } else {
                fetchContent();
            }
        }, [pattern]);

        const fetchContent = async () => {
            setIsFetching(true);
            try {
                const data = await wp.apiFetch({ 
                    path: `guten-cloud/v2/pattern-content?source=${pattern.source}&path=${encodeURIComponent(pattern.path)}` 
                });
                if (data.content) {
                    setBlocks(parse(data.content));
                }
            } catch (error) {
                console.error('GCP: Failed to fetch pattern content for preview:', error);
            } finally {
                setIsFetching(false);
            }
        };

        return createElement('div', { 
            className: 'gcp-pattern-item',
            onClick: () => onSelect(pattern)
        },
            createElement('div', { className: 'gcp-pattern-preview' }, 
                (isFetching || !blocks) ? createElement('div', { className: 'gcp-preview-loading' }, createElement(Spinner)) : 
                createElement('div', { className: 'gcp-block-preview-wrapper' },
                    createElement(BlockPreview, { 
                        blocks: blocks,
                        viewportWidth: 1200 
                    })
                )
            ),
            createElement('div', { className: 'gcp-pattern-info' },
                createElement('span', { className: 'gcp-pattern-name' }, pattern.name),
                createElement('span', { className: 'gcp-pattern-category' }, pattern.category)
            )
        );
    };

    const GCPModal = ({ onClose }) => {
        const [activeSource, setActiveSource] = useState('github');
        const [patterns, setPatterns] = useState([]);
        const [isLoading, setIsLoading] = useState(false);
        const [searchQuery, setSearchQuery] = useState('');
        
        const [activeTab, setActiveTab] = useState('General');
        const [activeCategory, setActiveCategory] = useState('ALL');

        const sources = [
            { id: 'github', label: 'Github' },
            { id: 'gdrive', label: 'Google Drive' },
            { id: 'local', label: 'Server Path' }
        ];

        const fetchPatterns = async () => {
            setIsLoading(true);
            try {
                const data = await wp.apiFetch({ 
                    path: `guten-cloud/v2/patterns?source=${activeSource}` 
                });
                const patternsData = Array.isArray(data) ? data : [];
                setPatterns(patternsData);
                
                if (patternsData.length > 0) {
                    // Try to find a sensible default tab
                    const uniqueTabs = [...new Set(patternsData.map(p => p.tab || 'General'))];
                    setActiveTab(uniqueTabs[0] || 'General');
                    setActiveCategory('ALL');
                }
            } catch (error) {
                console.error('GCP: Failed to fetch patterns:', error);
            } finally {
                setIsLoading(false);
            }
        };

        useEffect(() => {
            fetchPatterns();
        }, [activeSource]);

        const tabs = useMemo(() => {
            const uniqueTabs = [...new Set(patterns.map(p => p.tab || 'General'))];
            return uniqueTabs.sort();
        }, [patterns]);

        const categories = useMemo(() => {
            const relevantPatterns = patterns.filter(p => p.tab === activeTab);
            const uniqueCats = [...new Set(relevantPatterns.map(p => p.category || 'Uncategorized'))];
            return uniqueCats.sort();
        }, [patterns, activeTab]);

        const filteredPatterns = useMemo(() => {
            return patterns.filter(p => {
                const matchesTab = (p.tab === activeTab);
                const matchesCategory = (activeCategory === 'ALL' || p.category === activeCategory);
                const matchesSearch = !searchQuery || 
                    (p.name && p.name.toLowerCase().includes(searchQuery.toLowerCase()));
                
                return matchesTab && matchesCategory && matchesSearch;
            });
        }, [patterns, activeTab, activeCategory, searchQuery]);

        const insertPattern = async (pattern) => {
            try {
                // If content is already loaded in pattern object, use it. Otherwise fetch.
                let content = pattern.content;
                if (!content) {
                    const data = await wp.apiFetch({ 
                        path: `guten-cloud/v2/pattern-content?source=${pattern.source}&path=${encodeURIComponent(pattern.path)}` 
                    });
                    content = data.content;
                }
                
                if (content) {
                    const blocks = parse(content);
                    wp.data.dispatch('core/block-editor').insertBlocks(blocks);
                    onClose();
                }
            } catch (error) {
                console.error('GCP: Failed to insert pattern:', error);
            }
        };

        return createElement('div', { className: 'gcp-modal-root' },
            createElement('div', { className: 'gcp-modal-top-bar' },
                createElement('div', { className: 'gcp-source-tabs' },
                    sources.map(source => 
                        createElement(Button, {
                            key: source.id,
                            isPrimary: activeSource === source.id,
                            onClick: () => setActiveSource(source.id),
                            className: 'gcp-source-btn'
                        }, source.label)
                    )
                ),
                createElement(TextControl, {
                    placeholder: 'Search patterns...',
                    value: searchQuery,
                    onChange: (val) => setSearchQuery(val),
                    className: 'gcp-search-input'
                })
            ),

            tabs.length > 0 && createElement('div', { className: 'gcp-tab-navigation' },
                tabs.map(tab => 
                    createElement(Button, {
                        key: tab,
                        className: `gcp-nav-tab ${activeTab === tab ? 'is-active' : ''}`,
                        onClick: () => {
                            setActiveTab(tab);
                            setActiveCategory('ALL');
                        }
                    }, tab)
                )
            ),

            createElement('div', { className: 'gcp-modal-main' },
                createElement('div', { className: 'gcp-sidebar' },
                    createElement('div', { className: 'gcp-sidebar-header' }, 'Categories'),
                    createElement(Button, {
                        className: `gcp-sidebar-item ${activeCategory === 'ALL' ? 'is-active' : ''}`,
                        onClick: () => setActiveCategory('ALL')
                    }, 'All Categories'),
                    categories.map(cat => 
                        createElement(Button, {
                            key: cat,
                            className: `gcp-sidebar-item ${activeCategory === cat ? 'is-active' : ''}`,
                            onClick: () => setActiveCategory(cat)
                        }, cat)
                    )
                ),

                createElement('div', { className: 'gcp-content' },
                    isLoading ? createElement(Spinner) : 
                    filteredPatterns.length === 0 ? createElement('div', { className: 'gcp-no-patterns' }, 'No patterns found.') :
                    createElement('div', { className: 'gcp-pattern-grid' },
                        filteredPatterns.map(pattern => 
                            createElement(PatternPreviewItem, { 
                                key: `${pattern.source}-${pattern.path}`, 
                                pattern: pattern,
                                onSelect: insertPattern
                            })
                        )
                    )
                )
            )
        );
    };

    const GcpTrigger = () => {
        const [isOpen, setIsOpen] = useState(false);

        return createElement(Fragment, {},
            createElement(Button, {
                icon: 'cloud',
                label: 'Guten Cloud',
                onClick: () => setIsOpen(true),
                isPrimary: true,
                className: 'gcp-trigger-button'
            }, 'Guten Cloud'),
            isOpen && createElement(Modal, {
                title: 'Guten Cloud Patterns',
                onRequestClose: () => setIsOpen(false),
                isFullScreen: true,
                className: 'gcp-pattern-modal'
            }, createElement(GCPModal, { onClose: () => setIsOpen(false) }))
        );
    };

    wp.domReady(() => {
        const addGcpButton = () => {
            const toolbar = document.querySelector('.edit-post-header-toolbar');
            const existingButton = document.querySelector('.gcp-button-container');
            if (toolbar && !existingButton) {
                const buttonContainer = document.createElement('div');
                buttonContainer.className = 'gcp-button-container';
                buttonContainer.style.marginLeft = '10px';
                toolbar.appendChild(buttonContainer);
                wp.element.render(createElement(GcpTrigger), buttonContainer);
            }
        };

        addGcpButton();
        const observer = new MutationObserver(addGcpButton);
        observer.observe(document.body, { childList: true, subtree: true });
    });

})();
