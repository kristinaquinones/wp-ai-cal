(function($) {
    'use strict';

    const Calendar = {
        currentDate: new Date(),
        posts: [],
        listPosts: [],
        draggedPost: null,
        currentView: 'calendar',
        listPage: 1,
        listPerPage: 20,
        listTotal: 0,
        listPages: 0,
        confirmCallback: null,
        listFilters: {
            search: '',
            status: ''
        },
        listLoading: false,

        init: function() {
            this.bindEvents();
            this.render();
            this.loadPosts();
        },

        bindEvents: function() {
            $('.aiec-nav-prev').on('click', () => this.navigate(-1));
            $('.aiec-nav-next').on('click', () => this.navigate(1));
            $('.aiec-nav-today').on('click', () => this.goToToday());
            $('.aiec-modal-close').on('click', () => this.closeModal());
            $('.aiec-confirm-cancel').on('click', () => this.closeConfirmModal());
            $(document).on('click', '#aiec-confirm-modal', (e) => {
                if ($(e.target).hasClass('aiec-modal')) {
                    this.closeConfirmModal();
                }
            });
            
            // Prevent link clicks from interfering with delete button
            $(document).on('click', '.aiec-modal-post-link', (e) => {
                // Only prevent if clicking near the delete button
                const $link = $(e.currentTarget);
                const $li = $link.closest('.aiec-modal-post-item');
                const clickX = e.pageX;
                const $deleteBtn = $li.find('.aiec-delete-post');
                if ($deleteBtn.length) {
                    const btnOffset = $deleteBtn.offset();
                    const btnWidth = $deleteBtn.outerWidth();
                    // If click is in the delete button area, prevent link navigation
                    if (clickX >= btnOffset.left && clickX <= btnOffset.left + btnWidth) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }
            });
            $('.aiec-get-suggestions').on('click', () => this.getSuggestions());
            
            // View toggle
            $('.aiec-view-btn').on('click', (e) => {
                const view = $(e.currentTarget).data('view');
                this.switchView(view);
            });
            
            // List view events
            $('.aiec-search-input').on('input', () => this.handleListFilter());
            $('.aiec-status-filter').on('change', () => {
                this.saveFilterState();
                this.handleListFilter();
            });
            $('.aiec-new-post-list').on('click', () => {
                window.location.href = aiecData.newPostUrl;
            });
            $('.aiec-get-suggestions-list').on('click', () => this.getSuggestionsForList());
            $('.aiec-clear-filters').on('click', () => this.clearFilters());

            // Persist date picker selection
            $('.aiec-date-picker-list').on('change', () => {
                this.saveFilterState();
            });

            // Restore persisted filters
            this.restoreFilterState();
            
            // Tooltips for calendar posts
            $(document).on('mouseenter', '.aiec-post', (e) => this.showTooltip(e.currentTarget));
            $(document).on('mouseleave', '.aiec-post', () => this.hideTooltip());
            
            // Tooltips for list view titles
            $(document).on('mouseenter', '.aiec-col-title a', (e) => this.showTooltip(e.currentTarget));
            $(document).on('mouseleave', '.aiec-col-title a', () => this.hideTooltip());
            
            // Hide tooltip on scroll
            $(window).on('scroll', () => this.hideTooltip());

            // Click handler for post titles - open edit URL
            $(document).on('click', '.aiec-post', (e) => {
                e.stopPropagation(); // Prevent day click handler from firing
                const $post = $(e.currentTarget);
                const editUrl = $post.data('edit-url');
                if (editUrl) {
                    window.open(editUrl, '_blank');
                }
            });

            $(document).on('click', '.aiec-day', (e) => {
                // Don't open modal if clicking on a post (post click handler will handle it)
                if ($(e.target).hasClass('aiec-post') || $(e.target).closest('.aiec-post').length) {
                    return;
                }
                // Don't open modal if we just finished dragging
                if (this.justDropped) {
                    this.justDropped = false;
                    return;
                }
                // Don't open modal for past dates
                const date = $(e.currentTarget).data('date');
                if (this.isPastDate(date)) {
                    return;
                }
                if (date) this.openModal(date);
            });

            // Delete/trash post (try, show errors if any)
            $(document).on('click', '.aiec-delete-post', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(this);
                const postId = parseInt($btn.data('post-id'), 10);
                
                if (!postId) {
                    console.error('AIEC delete: invalid post ID on button', $btn);
                    alert('Invalid post ID.');
                    return;
                }
                
                const self = Calendar;
                console.log('AIEC delete: initiating', { postId });
                
                // Use native confirm to avoid modal issues
                const confirmed = window.confirm('Are you sure you want to trash this post? It will be moved to the trash and can be restored later.');
                if (!confirmed) return;
                
                const modalDate = $('#aiec-modal').data('date');
                const $postItem = $btn.closest('.aiec-modal-post-item');
                const isInModal = $postItem.length > 0 && modalDate;
                
                // Disable button while processing
                $btn.prop('disabled', true);
                
                $.ajax({
                    url: aiecData.ajaxUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aiec_trash_post',
                        nonce: aiecData.nonce,
                        post_id: postId
                    }
                }).done((response) => {
                    console.log('AIEC delete response', response);
                    $btn.prop('disabled', false);
                    
                    if (response.success) {
                        // Update local arrays
                        self.posts = self.posts.filter(p => p.id !== postId);
                        self.listPosts = self.listPosts.filter(p => p.id !== postId);
                        
                        // Remove from calendar UI
                        const $calendarPost = $(`.aiec-post[data-post-id="${postId}"]`);
                        $calendarPost.remove();
                        
                        // Remove from modal UI
                        if (isInModal) {
                            $postItem.remove();
                            const remainingPosts = $('.aiec-modal-post-item').length;
                            if (remainingPosts === 0) {
                                const isPast = self.isPastDate(modalDate);
                                const noPostsMsg = isPast
                                    ? '<p class="aiec-no-posts">No posts on this day.</p>'
                                    : '<p class="aiec-no-posts">No posts scheduled for this day.</p>';
                                $('.aiec-modal-posts').html(noPostsMsg);
                            }
                        }
                        
                        // Refresh data to stay in sync
                        if (self.currentView === 'list') {
                            self.loadListPosts();
                        } else {
                            self.loadPosts(() => {
                                if (modalDate) {
                                    self.openModal(modalDate);
                                }
                            });
                        }
                    } else {
                        console.error('AIEC delete failed response:', response);
                        alert((response && response.data) ? response.data : 'Failed to trash post');
                    }
                }).fail((xhr, status, error) => {
                    $btn.prop('disabled', false);
                    console.error('AIEC delete request error:', status, error, xhr && xhr.responseText);
                    alert('Network error. Please try again.');
                });
            });
            
            // Also handle clicks on the dashicons inside the delete button
            $(document).on('click', '.aiec-delete-post .dashicons', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).closest('.aiec-delete-post').trigger('click');
            });

            // Create draft from suggestion
            $(document).on('click', '.aiec-create-draft', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                // Decode base64 encoded data
                const title = decodeURIComponent(atob($btn.data('title')));
                const desc = decodeURIComponent(atob($btn.data('desc') || ''));
                const dateOverride = $btn.data('date') || null;
                this.createDraft(title, desc, dateOverride);
            });

            $(document).on('click', '.aiec-modal', (e) => {
                if ($(e.target).hasClass('aiec-modal')) {
                    this.closeModal();
                }
            });

            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') this.closeModal();
            });

            // Drag and drop events
            $(document).on('dragstart', '.aiec-post.aiec-draggable', (e) => this.handleDragStart(e));
            $(document).on('dragend', '.aiec-post.aiec-draggable', (e) => this.handleDragEnd(e));
            $(document).on('dragover', '.aiec-day', (e) => this.handleDragOver(e));
            $(document).on('dragleave', '.aiec-day', (e) => this.handleDragLeave(e));
            $(document).on('drop', '.aiec-day', (e) => this.handleDrop(e));
        },

        handleDragStart: function(e) {
            const $post = $(e.currentTarget);
            const postId = $post.data('post-id');

            this.draggedPost = this.posts.find(p => p.id === postId);

            if (!this.draggedPost) return;

            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/plain', postId);

            $post.addClass('aiec-dragging');

            // Add drop zone styling to all days after a brief delay
            setTimeout(() => {
                $('.aiec-day').addClass('aiec-drop-zone');
            }, 0);
        },

        handleDragEnd: function(e) {
            $(e.currentTarget).removeClass('aiec-dragging');
            $('.aiec-day').removeClass('aiec-drop-zone aiec-drag-over');
            this.draggedPost = null;
        },

        handleDragOver: function(e) {
            if (!this.draggedPost) return;

            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            $(e.currentTarget).addClass('aiec-drag-over');
        },

        handleDragLeave: function(e) {
            $(e.currentTarget).removeClass('aiec-drag-over');
        },

        handleDrop: function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $day = $(e.currentTarget);
            $day.removeClass('aiec-drag-over');
            $('.aiec-day').removeClass('aiec-drop-zone');

            if (!this.draggedPost) return;

            const newDate = $day.data('date');
            const oldDate = this.draggedPost.date.split(' ')[0];

            // Don't do anything if dropped on the same day
            if (newDate === oldDate) {
                this.draggedPost = null;
                return;
            }

            this.justDropped = true;
            this.updatePostDate(this.draggedPost.id, newDate);
        },

        updatePostDate: function(postId, newDate) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;

            // Preserve the time portion, just change the date
            const oldDateTime = post.date.split(' ');
            const time = oldDateTime[1] || '09:00:00';
            const newDateTime = `${newDate} ${time}`;

            // Optimistically update UI
            const oldDate = post.date.split(' ')[0];
            post.date = newDateTime;
            this.renderPosts();

            // Send to server
            $.post(aiecData.ajaxUrl, {
                action: 'aiec_update_post_date',
                nonce: aiecData.nonce,
                post_id: postId,
                new_date: newDateTime
            }, (response) => {
                if (!response.success) {
                    // Revert on failure
                    post.date = `${oldDate} ${time}`;
                    this.renderPosts();
                    alert(response.data || 'Failed to update post date');
                }
            }).fail(() => {
                // Revert on network error
                post.date = `${oldDate} ${time}`;
                this.renderPosts();
                alert('Network error. Please try again.');
            });
        },

        navigate: function(direction) {
            this.currentDate.setMonth(this.currentDate.getMonth() + direction);
            this.render();
            this.loadPosts();
        },

        goToToday: function() {
            this.currentDate = new Date();
            this.render();
            this.loadPosts();
        },

        render: function() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();

            const monthNames = aiecData.strings.months;

            $('.aiec-month-title').text(`${monthNames[month]} ${year}`);

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            let html = '';
            const today = new Date();
            const todayStr = this.formatDate(today);

            // Previous month days
            for (let i = firstDay - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                const date = new Date(year, month - 1, day);
                const dateStr = this.formatDate(date);
                const isPast = this.isPastDate(dateStr);
                html += `<div class="aiec-day aiec-day-other${isPast ? ' aiec-day-past' : ''}" data-date="${dateStr}">
                    <span class="aiec-day-number">${day}</span>
                    <div class="aiec-day-posts"></div>
                </div>`;
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const dateStr = this.formatDate(date);
                const isToday = dateStr === todayStr;
                const isPast = this.isPastDate(dateStr);

                html += `<div class="aiec-day${isToday ? ' aiec-day-today' : ''}${isPast ? ' aiec-day-past' : ''}" data-date="${dateStr}">
                    <span class="aiec-day-number">${day}</span>
                    <div class="aiec-day-posts"></div>
                </div>`;
            }

            // Next month days
            const totalCells = Math.ceil((firstDay + daysInMonth) / 7) * 7;
            const remainingDays = totalCells - (firstDay + daysInMonth);

            for (let day = 1; day <= remainingDays; day++) {
                const date = new Date(year, month + 1, day);
                html += `<div class="aiec-day aiec-day-other" data-date="${this.formatDate(date)}">
                    <span class="aiec-day-number">${day}</span>
                    <div class="aiec-day-posts"></div>
                </div>`;
            }

            $('.aiec-days').html(html);
            this.renderPosts();
        },

        formatDate: function(date) {
            return date.toISOString().split('T')[0];
        },

        isPastDate: function(dateStr) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const date = new Date(dateStr + 'T00:00:00');
            return date < today;
        },

        getRandomFutureTime: function() {
            const now = new Date();
            // Random hour between current hour+1 and 20 (8 PM)
            const minHour = Math.max(now.getHours() + 1, 9);
            const hour = Math.floor(Math.random() * (20 - minHour + 1)) + minHour;
            const minute = Math.floor(Math.random() * 4) * 15; // 0, 15, 30, or 45
            return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}:00`;
        },

        createDraft: function(title, desc, dateOverride) {
            const getListSelectedDate = () => {
                const $dateInput = $('.aiec-date-picker-list');
                const selected = $dateInput.length ? $dateInput.val() : '';
                return selected || this.formatDate(new Date());
            };

            const date = dateOverride
                || getListSelectedDate()
                || $('#aiec-modal').data('date')
                || this.formatDate(new Date());
            const time = this.getRandomFutureTime();
            const dateTime = `${date} ${time}`;

            // Clean up title (remove "Title:" prefix if present)
            const cleanTitle = title.replace(/^Title:\s*/i, '').trim();
            const cleanDesc = desc.replace(/^Desc:\s*/i, '').trim();

            $.post(aiecData.ajaxUrl, {
                action: 'aiec_create_draft',
                nonce: aiecData.nonce,
                title: cleanTitle,
                description: cleanDesc,
                date: dateTime
            }, (response) => {
                if (response.success) {
                    // Open edit URL immediately if available
                    if (response.data.editUrl) {
                        window.open(response.data.editUrl, '_blank');
                    }
                    
                    // Reload posts based on current view
                    if (this.currentView === 'list') {
                        this.loadListPosts();
                    } else {
                        this.loadPosts();
                        this.closeModal();
                    }
                } else {
                    alert(response.data || 'Failed to create draft');
                }
            }).fail(() => {
                alert('Network error. Please try again.');
            });
        },

        loadPosts: function(callback) {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();

            const start = new Date(year, month - 1, 1);
            const end = new Date(year, month + 2, 0);

            $.post(aiecData.ajaxUrl, {
                action: 'aiec_get_posts',
                nonce: aiecData.nonce,
                start: this.formatDate(start),
                end: this.formatDate(end)
            }, (response) => {
                if (response.success) {
                    this.posts = response.data;
                    this.renderPosts();
                    // Execute callback if provided
                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            });
        },

        renderPosts: function() {
            $('.aiec-day-posts').empty();

            this.posts.forEach(post => {
                const postDate = post.date.split(' ')[0];
                const $day = $(`.aiec-day[data-date="${postDate}"]`);

                if ($day.length) {
                    const statusClass = `aiec-status-${post.status}`;
                    const isDraggable = ['draft', 'pending', 'future'].includes(post.status);
                    const draggableClass = isDraggable ? 'aiec-draggable' : '';

                    const $post = $(`<div class="aiec-post ${statusClass} ${draggableClass}"
                        data-post-id="${post.id}"
                        data-edit-url="${post.editUrl || ''}"
                        data-full-title="${this.escapeHtml(post.title)}">
                        ${this.escapeHtml(post.title)}
                    </div>`);

                    // Set draggable property directly on the DOM element
                    if (isDraggable) {
                        $post[0].draggable = true;
                    }

                    $day.find('.aiec-day-posts').append($post);
                }
            });
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        openModal: function(date) {
            const dayPosts = this.posts.filter(p => p.date.split(' ')[0] === date);
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            const isPast = this.isPastDate(date);

            $('.aiec-modal-title').text(formattedDate);

            let postsHtml = '';
            if (dayPosts.length) {
                postsHtml = '<ul class="aiec-post-list">';
                dayPosts.forEach(post => {
                    postsHtml += `<li class="aiec-modal-post-item" data-post-id="${post.id}">
                        <span class="aiec-post-status aiec-status-${post.status}">${post.status}</span>
                        <a href="${post.editUrl}" target="_blank" class="aiec-modal-post-link">${this.escapeHtml(post.title)}</a>
                        <button type="button" class="aiec-delete-post" data-post-id="${post.id}" title="Trash post">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </li>`;
                });
                postsHtml += '</ul>';
            } else {
                postsHtml = isPast
                    ? '<p class="aiec-no-posts">No posts on this day.</p>'
                    : '<p class="aiec-no-posts">No posts scheduled for this day.</p>';
            }

            $('.aiec-modal-posts').html(postsHtml);

            // Hide action buttons for past dates
            if (isPast) {
                $('.aiec-new-post, .aiec-get-suggestions').hide();
            } else {
                $('.aiec-new-post').attr('href', aiecData.newPostUrl).show();
                $('.aiec-get-suggestions').show();
            }

            $('.aiec-suggestions').hide();
            $('.aiec-suggestions-content').empty();

            $('#aiec-modal').data('date', date).fadeIn(200);
        },

        closeModal: function() {
            $('#aiec-modal').fadeOut(200);
        },

        showConfirmModal: function(message, onConfirm) {
            console.log('AIEC confirm modal open');
            $('.aiec-confirm-message').text(message);
            $('#aiec-confirm-modal').fadeIn(200);
            
            // Store the confirm callback
            this.confirmCallback = onConfirm;
            
            // Handle confirm button click
            $('.aiec-confirm-ok').off('click.confirm').on('click.confirm', () => {
                console.log('AIEC confirm clicked');
                this.closeConfirmModal();
                if (this.confirmCallback) {
                    this.confirmCallback();
                }
            });
        },

        closeConfirmModal: function() {
            $('#aiec-confirm-modal').fadeOut(200);
            this.confirmCallback = null;
        },

        getSuggestions: function() {
            if (!aiecData.hasApiKey) {
                alert(aiecData.strings.noApiKey);
                return;
            }

            const date = $('#aiec-modal').data('date');
            const $btn = $('.aiec-get-suggestions');
            const $suggestions = $('.aiec-suggestions');
            const $content = $('.aiec-suggestions-content');

            $btn.prop('disabled', true).text(aiecData.strings.loading);

            $.post(aiecData.ajaxUrl, {
                action: 'aiec_get_suggestions',
                nonce: aiecData.nonce,
                date: date
            }, (response) => {
                $btn.prop('disabled', false).text(aiecData.strings.getSuggestions);

                if (response.success) {
                    $content.html(this.formatSuggestions(response.data));
                    $suggestions.slideDown();
                } else {
                    alert(response.data || 'Error getting suggestions');
                }
            }).fail(() => {
                $btn.prop('disabled', false).text(aiecData.strings.getSuggestions);
                alert('Network error. Please try again.');
            });
        },

        formatSuggestions: function(text) {
            // Parse lines - each suggestion is one line with "Title: X | Desc: Y" format
            const lines = text.split('\n').filter(l => l.trim() && l.includes('Title:'));

            if (lines.length === 0) {
                return `<div class="aiec-suggestion-item">${this.escapeHtml(text)}</div>`;
            }

            return lines.map(line => {
                const parts = line.split('|').map(p => p.trim());
                const title = parts[0] || line;
                const desc = parts[1] || '';

                // Base64 encode to preserve special characters in data attributes
                const titleData = btoa(encodeURIComponent(title));
                const descData = btoa(encodeURIComponent(desc));

                const listDate = $('.aiec-date-picker-list').val();
                const date = this.currentView === 'list'
                    ? (listDate || this.formatDate(new Date()))
                    : ($('#aiec-modal').data('date') || this.formatDate(new Date()));
                return `<div class="aiec-suggestion-item">
                    <strong>${this.escapeHtml(title)}</strong><br>
                    <button type="button" class="aiec-btn aiec-btn-small aiec-create-draft" data-title="${titleData}" data-desc="${descData}" data-date="${date}">Create Draft</button>
                </div>`;
            }).join('');
        },

        switchView: function(view) {
            this.currentView = view;
            $('.aiec-view-btn').removeClass('aiec-btn-primary');
            $(`.aiec-view-btn[data-view="${view}"]`).addClass('aiec-btn-primary');
            $('.aiec-view-container').hide();
            $(`.aiec-view-container[data-view="${view}"]`).show();
            
            if (view === 'list') {
                this.loadListPosts();
            }
        },

        handleListFilter: function() {
            this.listFilters.search = $('.aiec-search-input').val();
            this.listFilters.status = $('.aiec-status-filter').val();
            this.listPage = 1;
            this.loadListPosts();
        },

        clearFilters: function() {
            $('.aiec-search-input').val('');
            $('.aiec-status-filter').val('');
            this.listFilters.search = '';
            this.listFilters.status = '';
            this.listPage = 1;
            this.saveFilterState();
            this.loadListPosts();
        },

        saveFilterState: function() {
            const status = $('.aiec-status-filter').val() || '';
            const date = $('.aiec-date-picker-list').val() || '';
            localStorage.setItem('aiec_list_status', status);
            localStorage.setItem('aiec_list_date', date);
        },

        restoreFilterState: function() {
            const status = localStorage.getItem('aiec_list_status') || '';
            const date = localStorage.getItem('aiec_list_date') || '';
            if (status) {
                $('.aiec-status-filter').val(status);
                this.listFilters.status = status;
            }
            if (date) {
                $('.aiec-date-picker-list').val(date);
            }
        },

        renderListSkeleton: function() {
            const $tbody = $('.aiec-list-tbody');
            $tbody.empty();
            const rows = Array.from({ length: 3 }).map(() => `
                <tr class="aiec-skeleton-row">
                    <td class="aiec-col-drag"><div class="aiec-skel aiec-skel-circle"></div></td>
                    <td class="aiec-col-date"><div class="aiec-skel aiec-skel-line short"></div><div class="aiec-skel aiec-skel-line tiny"></div></td>
                    <td class="aiec-col-title"><div class="aiec-skel aiec-skel-line"></div></td>
                    <td class="aiec-col-status"><div class="aiec-skel aiec-skel-pill"></div></td>
                    <td class="aiec-col-actions"><div class="aiec-skel aiec-skel-pill small"></div><div class="aiec-skel aiec-skel-circle"></div></td>
                </tr>
            `).join('');
            $tbody.html(rows);
        },

        formatDisplayDate: function(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr + 'T00:00:00');
            if (Number.isNaN(date.getTime())) return '';
            return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        },

        loadListPosts: function() {
            this.listLoading = true;
            this.renderListSkeleton();
            $.post(aiecData.ajaxUrl, {
                action: 'aiec_get_all_posts',
                nonce: aiecData.nonce,
                page: this.listPage,
                per_page: this.listPerPage,
                search: this.listFilters.search,
                status: this.listFilters.status
            }, (response) => {
                this.listLoading = false;
                if (response.success) {
                    this.listPosts = response.data.posts;
                    this.listTotal = response.data.total;
                    this.listPages = response.data.pages;
                    this.renderListPosts();
                    this.renderListPagination();
                }
            });
        },

        renderListPosts: function() {
            const $tbody = $('.aiec-list-tbody');
            $tbody.empty();

            if (this.listLoading) {
                this.renderListSkeleton();
                return;
            }

            if (this.listPosts.length === 0) {
                $tbody.html('<tr><td colspan="5" style="text-align: center; padding: 40px; color: #666;">No posts found.</td></tr>');
                return;
            }

            this.listPosts.forEach((post, index) => {
                const postDate = new Date(post.date);
                const dateStr = postDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const timeStr = postDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                
                const statusClass = `aiec-status-${post.status}`;
                const statusLabels = {
                    'publish': 'Published',
                    'draft': 'Draft',
                    'pending': 'Pending',
                    'future': 'Scheduled'
                };
                const statusLabel = statusLabels[post.status] || post.status;
                
                const row = `
                    <tr class="aiec-list-row" data-post-id="${post.id}">
                        <td class="aiec-col-drag">
                        </td>
                        <td class="aiec-col-date">
                            <div class="aiec-list-date">${dateStr}</div>
                            <div class="aiec-list-time">${timeStr}</div>
                        </td>
                        <td class="aiec-col-title">
                            <a href="${post.editUrl}" target="_blank" data-full-title="${this.escapeHtml(post.title)}">${this.escapeHtml(post.title)}</a>
                        </td>
                        <td class="aiec-col-status">
                            <span class="aiec-status-badge ${statusClass}">${statusLabel}</span>
                        </td>
                        <td class="aiec-col-actions">
                            <a href="${post.editUrl}" target="_blank" class="aiec-btn aiec-btn-small">Edit</a>
                            <button type="button" class="aiec-delete-post aiec-btn-icon" data-post-id="${post.id}" title="Trash post">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                `;
                $tbody.append(row);
            });
        },

        renderListPagination: function() {
            const $pagination = $('.aiec-list-pagination');
            $pagination.empty();

            if (this.listPages <= 1) return;

            let html = '<div class="aiec-pagination">';
            
            // Previous button
            if (this.listPage > 1) {
                html += `<button type="button" class="aiec-btn aiec-pagination-btn" data-page="${this.listPage - 1}">← Previous</button>`;
            }
            
            // Page numbers
            const maxPages = Math.min(this.listPages, 10);
            let startPage = Math.max(1, this.listPage - 4);
            let endPage = Math.min(this.listPages, startPage + maxPages - 1);
            
            if (startPage > 1) {
                html += `<button type="button" class="aiec-btn aiec-pagination-btn" data-page="1">1</button>`;
                if (startPage > 2) {
                    html += '<span class="aiec-pagination-ellipsis">...</span>';
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const active = i === this.listPage ? 'aiec-btn-primary' : '';
                html += `<button type="button" class="aiec-btn aiec-pagination-btn ${active}" data-page="${i}">${i}</button>`;
            }
            
            if (endPage < this.listPages) {
                if (endPage < this.listPages - 1) {
                    html += '<span class="aiec-pagination-ellipsis">...</span>';
                }
                html += `<button type="button" class="aiec-btn aiec-pagination-btn" data-page="${this.listPages}">${this.listPages}</button>`;
            }
            
            // Next button
            if (this.listPage < this.listPages) {
                html += `<button type="button" class="aiec-btn aiec-pagination-btn" data-page="${this.listPage + 1}">Next →</button>`;
            }
            
            html += '</div>';
            html += `<div class="aiec-pagination-info">Showing ${((this.listPage - 1) * this.listPerPage) + 1}-${Math.min(this.listPage * this.listPerPage, this.listTotal)} of ${this.listTotal} posts</div>`;
            
            $pagination.html(html);
            
            $('.aiec-pagination-btn').on('click', (e) => {
                const page = parseInt($(e.currentTarget).data('page'));
                if (page && page !== this.listPage) {
                    this.listPage = page;
                    this.loadListPosts();
                    $('html, body').animate({ scrollTop: $('.aiec-list-card').offset().top - 20 }, 300);
                }
            });
        },

        getSuggestionsForList: function() {
            if (!aiecData.hasApiKey) {
                alert(aiecData.strings.noApiKey);
                return;
            }

            // Use selected date; fallback to today
            const dateInput = $('.aiec-date-picker-list');
            const selectedDate = dateInput.length ? dateInput.val() : '';
            const date = selectedDate || this.formatDate(new Date());
            const $btn = $('.aiec-get-suggestions-list');
            const $suggestions = $('.aiec-list-suggestions');
            const $content = $('.aiec-list-suggestions-content');
            const $titleDate = $('.aiec-suggestions-date');

            $btn.prop('disabled', true).text(aiecData.strings.loading);
            $titleDate.text('');

            $.post(aiecData.ajaxUrl, {
                action: 'aiec_get_suggestions',
                nonce: aiecData.nonce,
                date: date
            }, (response) => {
                $btn.prop('disabled', false).text(aiecData.strings.getSuggestions);

                if (response.success) {
                    $content.html(this.formatSuggestions(response.data));
                    $suggestions.slideDown();
                    const displayDate = this.formatDisplayDate(date);
                    $titleDate.text(displayDate ? ` — ${displayDate}` : '');
                } else {
                    alert(response.data || 'Error getting suggestions');
                }
            }).fail(() => {
                $btn.prop('disabled', false).text(aiecData.strings.getSuggestions);
                alert('Network error. Please try again.');
            });
        },

        showTooltip: function(element) {
            const $el = $(element);
            const title = $el.attr('data-full-title') || $el.text().trim() || $el.attr('title');
            
            if (!title) return;
            
            // Check if text is truncated (only show tooltip if needed)
            const isTruncated = $el[0].scrollWidth > $el[0].clientWidth || 
                               $el[0].scrollHeight > $el[0].clientHeight;
            
            // For calendar posts, always show if title exists (they're always truncated to 2 lines)
            const isCalendarPost = $el.hasClass('aiec-post');
            
            if (!isTruncated && !isCalendarPost) return;
            
            // Remove native title to prevent double tooltip
            $el.attr('data-original-title', $el.attr('title') || '');
            $el.removeAttr('title');
            
            // Create or get tooltip element
            let $tooltip = $('#aiec-tooltip');
            if ($tooltip.length === 0) {
                $tooltip = $('<div id="aiec-tooltip"></div>');
                $('body').append($tooltip);
            }
            
            $tooltip.text(title).show();
            
            // Position tooltip
            this.positionTooltip($el, $tooltip);
        },

        hideTooltip: function() {
            const $tooltip = $('#aiec-tooltip');
            $tooltip.hide();
            
            // Restore original title attributes
            $('[data-original-title]').each(function() {
                const $el = $(this);
                $el.attr('title', $el.attr('data-original-title'));
                $el.removeAttr('data-original-title');
            });
        },

        positionTooltip: function($element, $tooltip) {
            // Show tooltip first to get accurate dimensions
            $tooltip.css({ visibility: 'hidden', display: 'block' });
            
            const offset = $element.offset();
            const width = $element.outerWidth();
            const height = $element.outerHeight();
            const tooltipWidth = $tooltip.outerWidth();
            const tooltipHeight = $tooltip.outerHeight();
            const scrollTop = $(window).scrollTop();
            const scrollLeft = $(window).scrollLeft();
            const windowWidth = $(window).width();
            const windowHeight = $(window).height();
            
            // Default position: below and centered
            let top = offset.top + height + 8;
            let left = offset.left + (width / 2) - (tooltipWidth / 2);
            let showAbove = false;
            
            // If tooltip would go below viewport, show above instead
            if (top + tooltipHeight > scrollTop + windowHeight - 10) {
                top = offset.top - tooltipHeight - 8;
                showAbove = true;
            }
            
            // Adjust horizontal position if tooltip goes off screen
            if (left + tooltipWidth > scrollLeft + windowWidth - 10) {
                left = scrollLeft + windowWidth - tooltipWidth - 10;
            }
            if (left < scrollLeft + 10) {
                left = scrollLeft + 10;
            }
            
            // Update arrow direction
            $tooltip.toggleClass('aiec-tooltip-above', showAbove);
            
            $tooltip.css({
                top: top + 'px',
                left: left + 'px',
                visibility: 'visible'
            });
        }
    };

    $(document).ready(() => Calendar.init());

})(jQuery);
