(function($) {
    'use strict';

    const Calendar = {
        currentDate: new Date(),
        posts: [],

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
                const date = $(e.currentTarget).data('date');
                if (date) this.openModal(date);
            });

            $(document).on('click', '.aiec-modal', (e) => {
                if ($(e.target).hasClass('aiec-modal')) {
                    this.closeModal();
                }
            });

            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') this.closeModal();
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

            const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];

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
                html += `<div class="aiec-day aiec-day-other" data-date="${this.formatDate(date)}">
                    <span class="aiec-day-number">${day}</span>
                    <div class="aiec-day-posts"></div>
                </div>`;
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const dateStr = this.formatDate(date);
                const isToday = dateStr === todayStr;

                html += `<div class="aiec-day${isToday ? ' aiec-day-today' : ''}" data-date="${dateStr}">
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
                    const $post = $(`<div class="aiec-post ${statusClass}" title="${this.escapeHtml(post.title)}">
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
                postsHtml = '<p class="aiec-no-posts">No posts scheduled for this day.</p>';
            }

            $('.aiec-modal-posts').html(postsHtml);

            const newPostUrl = `${window.location.origin}/wp-admin/post-new.php`;
            $('.aiec-new-post').attr('href', newPostUrl);

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
            // Parse the AI response and format it nicely
            const suggestions = text.split('---').filter(s => s.trim());

            if (suggestions.length === 0) {
                return `<div class="aiec-suggestion-item">${this.escapeHtml(text)}</div>`;
            }

            return suggestions.map(suggestion => {
                const lines = suggestion.trim().split('\n').filter(l => l.trim());
                let html = '<div class="aiec-suggestion-item">';

                lines.forEach(line => {
                    if (line.toLowerCase().startsWith('title:')) {
                        html += `<strong>${this.escapeHtml(line)}</strong><br>`;
                    } else {
                        html += `${this.escapeHtml(line)}<br>`;
                    }
                });

                html += '</div>';
                return html;
            }).join('');
        }
    };

    $(document).ready(() => Calendar.init());

})(jQuery);
