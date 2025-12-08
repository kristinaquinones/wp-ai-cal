(function($) {
    'use strict';

    const Calendar = {
        currentDate: new Date(),
        posts: [],
        draggedPost: null,

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
            $('.aiec-get-suggestions').on('click', () => this.getSuggestions());

            $(document).on('click', '.aiec-day', (e) => {
                // Don't open modal if we just finished dragging
                if (this.justDropped) {
                    this.justDropped = false;
                    return;
                }
                // Don't open modal for past dates unless clicking on a post
                const date = $(e.currentTarget).data('date');
                if (this.isPastDate(date) && !$(e.target).hasClass('aiec-post')) {
                    return;
                }
                if (date) this.openModal(date);
            });

            // Create draft from suggestion
            $(document).on('click', '.aiec-create-draft', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                // Decode base64 encoded data
                const title = decodeURIComponent(atob($btn.data('title')));
                const desc = decodeURIComponent(atob($btn.data('desc') || ''));
                this.createDraft(title, desc);
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

        createDraft: function(title, desc) {
            const date = $('#aiec-modal').data('date');
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
                    // Reload posts and close modal
                    this.loadPosts();
                    this.closeModal();
                } else {
                    alert(response.data || 'Failed to create draft');
                }
            }).fail(() => {
                alert('Network error. Please try again.');
            });
        },

        loadPosts: function() {
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
                    const draggableAttr = isDraggable ? 'draggable="true"' : '';

                    const $post = $(`<div class="aiec-post ${statusClass} ${draggableClass}"
                        data-post-id="${post.id}"
                        title="${this.escapeHtml(post.title)}"
                        ${draggableAttr}>
                        ${this.escapeHtml(post.title)}
                    </div>`);

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
                    postsHtml += `<li>
                        <span class="aiec-post-status aiec-status-${post.status}">${post.status}</span>
                        <a href="${post.editUrl}" target="_blank">${this.escapeHtml(post.title)}</a>
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

                return `<div class="aiec-suggestion-item">
                    <strong>${this.escapeHtml(title)}</strong><br>
                    ${desc ? this.escapeHtml(desc) + '<br>' : ''}
                    <button type="button" class="aiec-btn aiec-btn-small aiec-create-draft" data-title="${titleData}" data-desc="${descData}">Create Draft</button>
                </div>`;
            }).join('');
        }
    };

    $(document).ready(() => Calendar.init());

})(jQuery);
