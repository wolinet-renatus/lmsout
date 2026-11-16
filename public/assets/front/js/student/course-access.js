const getAppBaseUrl = () => ($('meta[name="app-url"]').attr('content') || window.location.origin).replace(/\/+$/, '');
const appUrl = (path) => `${getAppBaseUrl()}/${String(path).replace(/^\/+/, '')}`;

$(document).ready(function() {
    let quizTimer = null;
    let quizStartTime = null;
    let quizTimeLimit = null;
    let quizTimeExpired = false;

    if ($('.course-access-fullscreen').length) {
        $('header, footer, .breadcrumb-area, .back-to-top, .mouseCursor').hide();
        $('body').css('overflow', 'hidden');
    }

    $('#sidebar-toggle').on('click', function() {
        $('#course-sidebar-wrapper').toggleClass('open');
    });

    $(document).on('click', function(e) {
        if ($(window).width() <= 991) {
            if (!$(e.target).closest('.course-sidebar-wrapper, #sidebar-toggle').length) {
                $('#course-sidebar-wrapper').removeClass('open');
            }
        }
    });

    $(document).on('click', '.chapter-header', function(e) {
        if ($(e.target).hasClass('chapter-progress')) {
            return;
        }
        
        const $chapterContent = $(this).next('.chapter-content');
        const $icon = $(this).find('.chapter-toggle-icon');
        const $chapterSection = $(this).closest('.chapter-section');
        
        $chapterContent.slideToggle(300);
        $chapterSection.toggleClass('collapsed');
        
        if ($chapterSection.hasClass('collapsed')) {
            $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
        } else {
            $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
        }
    });

    $(document).on('click', '.quiz-option-label', function(e) {
        e.preventDefault();
        const radioInput = $(this).prev('input[type="radio"]');
        if (radioInput.length) {
            radioInput.prop('checked', true).trigger('change');
        }
    });

    $(document).on('click', '.lesson-item, .quiz-item, .assignment-item', function() {
        const itemId = $(this).data('item-id');
        const itemType = $(this).data('item-type');
        const targetCourseId = $(this).data('course-id');
        const isAccessible = $(this).attr('data-accessible') == '1';

        if (!isAccessible) {
            if (window.toastMagic) {
                window.toastMagic.warning('Content Locked', 'Please complete the previous lessons/quizzes/assignments before accessing this content.');
            } else {
                alert('Please complete the previous lessons/quizzes/assignments before accessing this content.');
            }
            return;
        }

        $('.lesson-item, .quiz-item, .assignment-item').removeClass('active');
        $(this).addClass('active');

        loadItem(itemId, itemType, targetCourseId);
    });

    function syncActiveSidebarItem() {
        $('.lesson-item, .quiz-item, .assignment-item').removeClass('active');
        const $activeItem = $(`.curriculum-item.${currentItemType}-item[data-item-id="${currentItemId}"]`);
        if ($activeItem.length) {
            $activeItem.addClass('active');
            const $chapterSection = $activeItem.closest('.chapter-section');
            if ($chapterSection.length && $chapterSection.hasClass('collapsed')) {
                $chapterSection.removeClass('collapsed');
                $chapterSection.find('.chapter-content').show();
                $chapterSection.find('.chapter-toggle-icon').removeClass('fa-chevron-right').addClass('fa-chevron-down');
            }
        }
    }

    function updateProgressSummary(summary) {
        if (!summary) return;
        const pct = parseInt(summary.percentage, 10) || 0;
        const completed = parseInt(summary.completed_items, 10) || 0;
        const total = parseInt(summary.total_items, 10) || 0;

        $('#course-progress-bar')
            .css('width', pct + '%')
            .attr('aria-valuenow', pct);
        $('#course-progress-text').text(`${completed}/${total} (${pct}%)`);
    }

    function updateNavigationButtons(navigation) {
        if (!navigation) return;
        currentNavigation = navigation;

        const hasPrev = Boolean(navigation.has_previous);
        const hasNext = Boolean(navigation.has_next);
        const nextAccessible = Boolean(navigation.next_accessible);

        $('#prev-btn')
            .prop('disabled', !hasPrev)
            .toggleClass('opacity-50', !hasPrev)
            .attr('title', hasPrev ? 'Previous' : 'First item in course');

        $('#next-btn')
            .prop('disabled', !hasNext)
            .toggleClass('opacity-50', !hasNext || !nextAccessible)
            .attr('title', !hasNext ? 'Last item in course' : (nextAccessible ? 'Next' : 'Complete current item to unlock Next'));
    }

    function extractAjaxError(xhr, fallback) {
        let errorMessage = fallback;
        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.errors) {
                const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                if (firstKey && Array.isArray(xhr.responseJSON.errors[firstKey]) && xhr.responseJSON.errors[firstKey][0]) {
                    return xhr.responseJSON.errors[firstKey][0];
                }
            }
            if (xhr.responseJSON.error) {
                errorMessage = xhr.responseJSON.error;
            } else if (xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
        }
        return errorMessage;
    }

    function loadItem(itemId, itemType, targetCourseId) {
        if (quizTimer) {
            clearInterval(quizTimer);
            quizTimer = null;
        }
        quizTimeExpired = false;

        const url = itemType === 'lesson' 
            ? appUrl(`student/courses/${targetCourseId}/lesson/load`)
            : itemType === 'quiz'
            ? appUrl(`student/courses/${targetCourseId}/quiz/${itemId}/load`)
            : appUrl(`student/courses/${targetCourseId}/assignment/${itemId}/load`);

        const data = {
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        if (itemType === 'lesson') {
            data.lesson_id = itemId;
            data.course_id = targetCourseId;
        } else {
            data.course_id = targetCourseId;
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#main-content').html(response.html);
                    currentItemId = itemId;
                    currentItemType = response.type || itemType;
                    
                    $('#current-item-title').text(response.title);
                    
                    let iconClass = 'fas fa-play-circle';
                    if (currentItemType === 'quiz') {
                        iconClass = 'fas fa-question-circle';
                    } else if (currentItemType === 'assignment') {
                        iconClass = 'fas fa-file-alt';
                    }
                    $('.course-header-play-icon').attr('class', iconClass + ' course-header-play-icon');
                    
                    let durationHtml = '';
                    if (response.duration && currentItemType === 'lesson') {
                        const duration = parseInt(response.duration, 10);
                        const hours = Math.floor(duration / 60);
                        const minutes = duration % 60;
                        const durationFormatted = hours > 0 
                            ? hours + ':' + String(minutes).padStart(2, '0')
                            : '0:' + String(minutes).padStart(2, '0');
                        durationHtml = `(${durationFormatted} min)`;
                    }
                    if (durationHtml) {
                        $('#current-item-duration, .course-header-duration').text(durationHtml).show();
                    } else {
                        $('#current-item-duration, .course-header-duration').text('').hide();
                    }

                    if (window.history && typeof window.history.replaceState === 'function') {
                        const nextUrl = `${appUrl(`student/courses/${targetCourseId}/access`)}?item_type=${encodeURIComponent(currentItemType)}&item_id=${encodeURIComponent(currentItemId)}`;
                        window.history.replaceState(null, '', nextUrl);
                    }

                    syncActiveSidebarItem();
                    updateNavigationButtons(response.navigation);
                    
                    if ($(window).width() <= 991) {
                        $('#course-sidebar-wrapper').removeClass('open');
                    }

                    if (itemType === 'lesson' && response.type === 'lesson') {
                        setTimeout(function() {
                            initializeVideoPlayer();
                        }, 100);
                    } else if (itemType === 'quiz' || response.type === 'quiz') {
                        if (!response.is_completed) {
                            setTimeout(function() {
                                initializeQuizTimer();
                            }, 100);
                        }
                    }
                }
            },
            error: function(xhr) {
                const errorMessage = extractAjaxError(xhr, 'Failed to load content');
                
                if (window.toastMagic) {
                    if (xhr.responseJSON && xhr.responseJSON.locked) {
                        window.toastMagic.warning('Content Locked', errorMessage);
                    } else {
                        window.toastMagic.error('Error', errorMessage);
                    }
                } else {
                    if (xhr.responseJSON && xhr.responseJSON.locked) {
                        alert(errorMessage);
                    } else {
                        alert('Error: ' + errorMessage);
                    }
                }
            }
        });
    }

    let lastReportedProgressPercent = -1;

    function initializeVideoPlayer() {
        lastReportedProgressPercent = -1;
        if (videoPlayer) {
            videoPlayer.dispose();
            videoPlayer = null;
        }

        const videoElement = document.getElementById('lesson-video-player');
        if (videoElement) {
            videoPlayer = videojs('lesson-video-player', {
                controls: true,
                responsive: true,
                fluid: true,
                playbackRates: [0.5, 0.75, 1, 1.25, 1.5, 2]
            });

            videoPlayer.ready(function() {
                const savedRate = parseFloat(localStorage.getItem('lmsout_playback_rate') || '1');
                if (savedRate && !isNaN(savedRate)) {
                    videoPlayer.playbackRate(savedRate);
                    $('#lmsout-speed-select').val(String(savedRate));
                }

                videoPlayer.on('ratechange', function() {
                    const currentRate = videoPlayer.playbackRate();
                    localStorage.setItem('lmsout_playback_rate', currentRate);
                    $('#lmsout-speed-select').val(String(currentRate));
                });

                videoPlayer.on('timeupdate', function() {
                    if (videoPlayer.duration()) {
                        const percentage = Math.round((videoPlayer.currentTime() / videoPlayer.duration()) * 100);
                        if (
                            percentage !== lastReportedProgressPercent &&
                            (Math.abs(percentage - lastReportedProgressPercent) >= 5 || (percentage >= 90 && lastReportedProgressPercent < 90))
                        ) {
                            lastReportedProgressPercent = percentage;
                            updateVideoProgress(percentage);
                        }
                    }
                });

                videoPlayer.on('ended', function() {
                    const lessonId = $('#mark-complete-btn').data('lesson-id');
                    const targetCourseId = $('#mark-complete-btn').data('course-id');
                    if (lessonId && targetCourseId) {
                        markComplete(lessonId, targetCourseId, true); 
                    }
                });
            });
        }
    }

    window.lmsoutVideoSeek = function(seconds) {
        if (videoPlayer && typeof videoPlayer.currentTime === 'function') {
            const nextTime = Math.max(0, (videoPlayer.currentTime() || 0) + Number(seconds));
            videoPlayer.currentTime(nextTime);
        }
    };

    window.lmsoutSetVideoSpeed = function(rate) {
        const numericRate = parseFloat(rate) || 1;
        localStorage.setItem('lmsout_playback_rate', numericRate);
        if (videoPlayer && typeof videoPlayer.playbackRate === 'function') {
            videoPlayer.playbackRate(numericRate);
        }
    };

    window.lmsoutTogglePiP = async function() {
        try {
            const rawVideo = document.querySelector('#lesson-video-player_html5_api') || document.querySelector('video');
            if (!rawVideo) return;
            if (document.pictureInPictureElement) {
                await document.exitPictureInPicture();
            } else if (rawVideo.requestPictureInPicture) {
                await rawVideo.requestPictureInPicture();
            }
        } catch (err) {
            console.warn('PiP not supported or failed:', err);
        }
    };

    window.lmsoutToggleTheater = function() {
        $('#course-sidebar-wrapper').toggleClass('d-none');
    };

    function updateVideoProgress(percentage) {
        const lessonId = $('#mark-complete-btn').data('lesson-id');
        const courseId = $('#mark-complete-btn').data('course-id');
        
        if (lessonId && courseId) {
            $.ajax({
                url: appUrl(`student/courses/${courseId}/lesson/progress`),
                method: 'POST',
                data: {
                    lesson_id: lessonId,
                    course_id: courseId,
                    percentage: percentage,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success && response.is_completed) {
                        $('#mark-complete-btn').html('<i class="fas fa-check-circle"></i> Completed').prop('disabled', true);
                    }
                }
            });
        }
    }

    function markComplete(lessonId, targetCourseId, autoNavigate = false) {
        const $btn = $('#mark-complete-btn');
        $btn.prop('disabled', true);

        $.ajax({
            url: appUrl(`student/courses/${targetCourseId}/lesson/complete`),
            method: 'POST',
            data: {
                lesson_id: lessonId,
                course_id: targetCourseId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $btn.html('<i class="fas fa-check-circle"></i> Completed')
                        .prop('disabled', true)
                        .css({ opacity: '0.6', cursor: 'not-allowed' });
                    
                    updateSidebarLessonStatus(lessonId, true);
                    updateNavigationButtons(response.navigation);
                    refreshSidebarAccessibility();
                    
                    if (window.toastMagic) {
                        window.toastMagic.success('Lesson Completed', 'Great job! Lesson marked as complete.');
                    }
                    
                    if (response.course_completed) {
                        setTimeout(function() {
                            if (window.toastMagic) {
                                window.toastMagic.success('Course Completed!', 'Congratulations! You have completed the entire course!');
                            }
                            window.location.href = appUrl(`student/courses/${targetCourseId}/completion`);
                        }, 2000);
                    } else if (autoNavigate && response.next_item) {
                        setTimeout(function() {
                            if (response.next_item.item_id && response.next_item.type) {
                                loadItem(response.next_item.item_id, response.next_item.type, targetCourseId);
                                if (window.toastMagic) {
                                    window.toastMagic.info('Moving Forward', 'Loading next item...');
                                }
                            }
                        }, 1500);
                    }
                } else {
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                const msg = extractAjaxError(xhr, 'Failed to mark lesson complete');
                if (window.toastMagic) {
                    window.toastMagic.error('Error', msg);
                } else {
                    alert(msg);
                }
            }
        });
    }

    $(document).on('click', '#mark-complete-btn', function() {
        const lessonId = $(this).data('lesson-id');
        const targetCourseId = $(this).data('course-id');
        markComplete(lessonId, targetCourseId);
    });

    function initializeQuizTimer() {
        quizTimeExpired = false;
        const timerElement = $('.floating-quiz-timer');
        if (timerElement.length) {
            timerElement.removeClass('warning danger');
            
            const timeLimitMinutes = parseInt(timerElement.data('time-limit')) || 0;
            
            if (timeLimitMinutes > 0) {
                quizTimeLimit = timeLimitMinutes * 60; 
                quizStartTime = Date.now();

                if (quizTimer) {
                    clearInterval(quizTimer);
                }

                updateTimerDisplay();

                quizTimer = setInterval(function() {
                    updateTimerDisplay();
                }, 1000);
            }
        }
    }

    function updateTimerDisplay() {
        if (!quizStartTime || !quizTimeLimit) return;

        const elapsed = Math.floor((Date.now() - quizStartTime) / 1000);
        const remaining = quizTimeLimit - elapsed;

        if (remaining <= 0) {
            clearInterval(quizTimer);
            $('.floating-timer-text').text('00:00');
            $('#time-taken').val(Math.floor((Date.now() - quizStartTime) / 60));
            
            $('#quiz-form button[type="submit"]').prop('disabled', true).html('<i class="fas fa-clock"></i> Time Expired');
            
            quizTimeExpired = true;
            submitQuiz(true);
            return;
        }

        const mins = Math.floor(remaining / 60);
        const secs = remaining % 60;
        const timeFormatted = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        
        $('.floating-timer-text').text(timeFormatted);
        
        const $timer = $('.floating-quiz-timer');
        $timer.removeClass('warning danger');
        
        if (remaining <= 60) {
            $timer.addClass('danger');
        } else if (remaining <= 300) {
            $timer.addClass('warning');
        }
    }

    function submitQuiz(allowExpired = false) {
        const $form = $('#quiz-form');
        if (!$form.length) return;

        if (!allowExpired && quizTimeExpired) {
            if (window.toastMagic) {
                window.toastMagic.warning('Time Expired', 'Quiz time has ended. Please refresh the page to submit again.');
            } else {
                alert('Quiz time has ended. Please refresh the page to submit again.');
            }
            return;
        }

        const quizId = $form.data('quiz-id');
        const targetCourseId = $form.data('course-id');

        const answers = [];
        $('.quiz-radio-input:checked').each(function() {
            const name = $(this).attr('name');
            const match = name.match(/answers\[(\d+)\]/);
            if (match) {
                const questionId = parseInt(match[1], 10);
                const selectedAnswer = parseInt($(this).val(), 10);
                answers.push({
                    question_id: questionId,
                    selected_answer: selectedAnswer
                });
            }
        });

        if (!allowExpired && answers.length === 0) {
            if (window.toastMagic) {
                window.toastMagic.warning('Answer Required', 'Please select at least one answer before submitting the quiz.');
            } else {
                alert('Please select at least one answer before submitting the quiz.');
            }
            return;
        }

        if (quizTimer && quizStartTime) {
            const elapsed = Math.floor((Date.now() - quizStartTime) / 1000);
            $('#time-taken').val(Math.floor(elapsed / 60));
            clearInterval(quizTimer);
            quizTimer = null;
        }

        $form.find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        $.ajax({
            url: appUrl(`student/courses/${targetCourseId}/quiz/${quizId}/submit`),
            method: 'POST',
            data: {
                quiz_id: quizId,
                answers: answers,
                time_taken: $('#time-taken').val() || 0,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    quizTimeExpired = true;
                    $('#main-content').html(response.html);
                    updateNavigationButtons(response.navigation);
                    updateProgressSummary(response.progress_summary);
                    
                    if (response.passed && response.quiz_id) {
                        updateSidebarQuizStatus(response.quiz_id, true);
                        refreshSidebarAccessibility();
                        
                        if (window.toastMagic) {
                            window.toastMagic.success('Quiz Passed!', 'Congratulations! You passed the quiz.');
                        }
                        
                        if (response.course_completed) {
                            setTimeout(function() {
                                if (window.toastMagic) {
                                    window.toastMagic.success('Course Completed!', 'Congratulations! You have completed the entire course!');
                                }
                                window.location.href = appUrl(`student/courses/${targetCourseId}/completion`);
                            }, 3000);
                        } else if (response.next_item && response.next_item.item_id) {
                            setTimeout(function() {
                                loadItem(response.next_item.item_id, response.next_item.type, targetCourseId);
                                if (window.toastMagic) {
                                    window.toastMagic.info('Moving Forward', 'Loading next item...');
                                }
                            }, 3000);
                        }
                    } else if (response.passed === false) {
                        if (window.toastMagic) {
                            window.toastMagic.warning('Quiz Failed', 'You did not pass the quiz. You can retake it.');
                        }
                    }
                }
            },
            error: function(xhr) {
                if (quizTimeExpired) {
                    if (window.toastMagic) {
                        window.toastMagic.error('Submission Failed', 'Quiz time has expired. Please refresh the page to submit again.');
                    } else {
                        alert('Quiz time has expired. Please refresh the page to submit again.');
                    }
                    return;
                }

                $form.find('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Quiz');

                const errorMessage = extractAjaxError(xhr, 'Failed to submit quiz');
                
                if (window.toastMagic) {
                    window.toastMagic.error('Submission Failed', errorMessage);
                } else {
                    alert('Error: ' + errorMessage);
                }
            }
        });
    }

    $(document).on('submit', '#quiz-form', function(e) {
        e.preventDefault();
        
        if (quizTimeExpired) {
            if (window.toastMagic) {
                window.toastMagic.warning('Time Expired', 'Quiz time has ended. Please refresh the page to submit again.');
            } else {
                alert('Quiz time has ended. Please refresh the page to submit again.');
            }
            return false;
        }
        
        submitQuiz();
        return false;
    });

    $(document).on('submit', '#assignment-form', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const formData = new FormData(this);
        const assignmentId = $form.data('assignment-id');
        const targetCourseId = $form.data('course-id');

        formData.append('assignment_id', assignmentId);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        $.ajax({
            url: appUrl(`student/courses/${targetCourseId}/assignment/${assignmentId}/submit`),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#main-content').html(response.html);
                    updateNavigationButtons(response.navigation);
                    updateProgressSummary(response.progress_summary);
                    
                    if (window.toastMagic) {
                        window.toastMagic.success('Assignment Submitted', 'Your assignment has been submitted successfully!');
                    }
                    
                    updateSidebarAssignmentStatus(assignmentId, true);
                    refreshSidebarAccessibility();
                    
                    if (response.course_completed) {
                        setTimeout(function() {
                            if (window.toastMagic) {
                                window.toastMagic.success('Course Completed!', 'Congratulations! You have completed the entire course!');
                            }
                            window.location.href = appUrl(`student/courses/${targetCourseId}/completion`);
                        }, 2500);
                    } else if (response.next_item && response.next_item.item_id) {
                        setTimeout(function() {
                            loadItem(response.next_item.item_id, response.next_item.type, targetCourseId);
                            if (window.toastMagic) {
                                window.toastMagic.info('Moving Forward', 'Loading next item...');
                            }
                        }, 2000);
                    }
                } else {
                    $submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Assignment');
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Assignment');
                const errorMessage = extractAjaxError(xhr, 'Failed to submit assignment');
                
                if (window.toastMagic) {
                    window.toastMagic.error('Submission Failed', errorMessage);
                } else {
                    alert('Error: ' + errorMessage);
                }
            }
        });
    });

    $(document).on('change', '#files', function() {
        const files = this.files;
        const preview = $('#file-preview');
        preview.empty();

        Array.from(files).forEach(function(file) {
            const fileItem = $('<div class="mb-2"></div>');
            fileItem.html(`<i class="fas fa-file"></i> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`);
            preview.append(fileItem);
        });
    });

    $(document).on('click', '.retake-quiz-btn', function(e) {
        e.preventDefault();
        const quizId = $(this).data('quiz-id');
        const courseIdFromBtn = $(this).data('course-id');
        const $btn = $(this);
        
        const targetCourseId = courseIdFromBtn || courseId;
        
        if (!targetCourseId) {
            if (window.toastMagic) {
                window.toastMagic.error('Error', 'Course ID not found. Please refresh the page.');
            } else {
                alert('Error: Course ID not found. Please refresh the page.');
            }
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        $.ajax({
            url: appUrl(`student/courses/${targetCourseId}/quiz/${quizId}/load`),
            method: 'POST',
            data: {
                retake: true,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#main-content').html(response.html);
                    currentItemId = quizId;
                    currentItemType = 'quiz';
                    
                    $('#current-item-title').text(response.title);
                    $('.course-header-play-icon').attr('class', 'fas fa-question-circle course-header-play-icon');
                    $('#current-item-duration, .course-header-duration').text('').hide();
                    updateNavigationButtons(response.navigation);
                    syncActiveSidebarItem();
                    
                    setTimeout(function() {
                        initializeQuizTimer();
                    }, 100);
                } else {
                    $btn.prop('disabled', false).html('<i class="fas fa-redo"></i> Retake Quiz');
                    if (window.toastMagic) {
                        window.toastMagic.error('Error', response.message || 'Failed to load quiz.');
                    }
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-redo"></i> Retake Quiz');
                let errorMessage = 'Failed to load quiz';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                if (window.toastMagic) {
                    window.toastMagic.error('Error', errorMessage);
                } else {
                    alert('Error: ' + errorMessage);
                }
            }
        });
    });

    $(document).on('click', '.view-results-btn', function(e) {
        e.preventDefault();
        const attemptId = $(this).data('attempt-id');
        const $btn = $(this);
        
        if (!courseId) {
            if (window.toastMagic) {
                window.toastMagic.error('Error', 'Course ID not found. Please refresh the page.');
            } else {
                alert('Error: Course ID not found. Please refresh the page.');
            }
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        $.ajax({
            url: appUrl(`student/courses/${courseId}/quiz/attempt/${attemptId}/results`),
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#main-content').html(response.html);
                } else {
                    $btn.prop('disabled', false).html('View Results <i class="fas fa-eye"></i>');
                    if (window.toastMagic) {
                        window.toastMagic.error('Error', response.message || 'Failed to load quiz results.');
                    } else {
                        alert('Error: ' + (response.message || 'Failed to load quiz results.'));
                    }
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('View Results <i class="fas fa-eye"></i>');
                let errorMessage = 'Failed to load quiz results';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                if (window.toastMagic) {
                    window.toastMagic.error('Error', errorMessage);
                } else {
                    alert('Error: ' + errorMessage);
                }
            }
        });
    });

    $('#next-btn').on('click', function() {
        navigateItem('next');
    });

    $('#prev-btn').on('click', function() {
        navigateItem('previous');
    });

    function navigateItem(direction) {
        const url = direction === 'next' 
            ? appUrl(`student/courses/${courseId}/item/${currentItemId}/next`)
            : appUrl(`student/courses/${courseId}/item/${currentItemId}/previous`);

        $.ajax({
            url: url,
            method: 'GET',
            data: {
                type: currentItemType
            },
            success: function(response) {
                if (response.success && response.item_id) {
                    loadItem(response.item_id, response.type, courseId);
                } else if (response && response.message) {
                    if (window.toastMagic) {
                        if (response.reason === 'locked') {
                            window.toastMagic.warning('Content Locked', response.message);
                        } else {
                            window.toastMagic.info('Navigation', response.message);
                        }
                    } else {
                        alert(response.message);
                    }
                }
            },
            error: function(xhr) {
                const msg = extractAjaxError(xhr, 'Unable to navigate to the requested item.');
                if (window.toastMagic) {
                    window.toastMagic.error('Navigation Error', msg);
                } else {
                    alert(msg);
                }
            }
        });
    }

    function updateSidebarLessonStatus(lessonId, isCompleted) {
        const $lessonItem = $(`.curriculum-item.lesson-item[data-item-id="${lessonId}"]`);
        if ($lessonItem.length) {
            const $statusIcon = $lessonItem.find('.curriculum-item-status');
            
            if (isCompleted) {
                $lessonItem.removeClass('locked');
                $statusIcon.html('<i class="fas fa-check-circle status-completed"></i>');
                
                const $topicSection = $lessonItem.closest('.chapter-section');
                if ($topicSection.length) {
                    const $progressBadge = $topicSection.find('.chapter-progress');
                    if ($progressBadge.length) {
                        const progressText = $progressBadge.text().split('/');
                        if (progressText.length === 2) {
                            const completed = parseInt(progressText[0]) + 1;
                            const total = parseInt(progressText[1]);
                            $progressBadge.text(`${completed}/${total}`);
                        }
                    }
                }
            }
        }
    }

    function updateSidebarQuizStatus(quizId, isCompleted) {
        const $quizItem = $(`.curriculum-item.quiz-item[data-item-id="${quizId}"]`);
        if ($quizItem.length) {
            const $statusIcon = $quizItem.find('.curriculum-item-status');
            
            if (isCompleted) {
                $quizItem.removeClass('locked');
                $statusIcon.html('<i class="fas fa-check-circle status-completed"></i>');
                
                const $topicSection = $quizItem.closest('.chapter-section');
                if ($topicSection.length) {
                    const $progressBadge = $topicSection.find('.chapter-progress');
                    if ($progressBadge.length) {
                        const progressText = $progressBadge.text().split('/');
                        if (progressText.length === 2) {
                            const completed = parseInt(progressText[0]) + 1;
                            const total = parseInt(progressText[1]);
                            $progressBadge.text(`${completed}/${total}`);
                        }
                    }
                }
            }
        }
    }

    function updateSidebarAssignmentStatus(assignmentId, isCompleted) {
        const $assignmentItem = $(`.curriculum-item.assignment-item[data-item-id="${assignmentId}"]`);
        if ($assignmentItem.length) {
            const $statusIcon = $assignmentItem.find('.curriculum-item-status');
            
            if (isCompleted) {
                $assignmentItem.removeClass('locked');
                $statusIcon.html('<i class="fas fa-check-circle status-completed"></i>');
                
                const $topicSection = $assignmentItem.closest('.chapter-section');
                if ($topicSection.length) {
                    const $progressBadge = $topicSection.find('.chapter-progress');
                    if ($progressBadge.length) {
                        const progressText = $progressBadge.text().split('/');
                        if (progressText.length === 2) {
                            const completed = parseInt(progressText[0]) + 1;
                            const total = parseInt(progressText[1]);
                            $progressBadge.text(`${completed}/${total}`);
                        }
                    }
                }
            }
        }
    }

    function refreshSidebarAccessibility() {
        $.ajax({
            url: appUrl(`student/courses/${courseId}/sidebar/refresh`),
            method: 'GET',
            data: {
                active_type: currentItemType,
                active_id: currentItemId
            },
            success: function(response) {
                if (response.success) {
                    $('#course-sidebar').html(response.html);
                    updateProgressSummary(response.progress_summary);
                    syncActiveSidebarItem();
                    
                    if (response.accessibleItems) {
                        Object.keys(response.accessibleItems).forEach(function(key) {
                            const isAccessible = response.accessibleItems[key];
                            const parts = key.split('_');
                            const type = parts[0];
                            const itemId = parts.slice(1).join('_');
                            const $item = $(`.curriculum-item.${type}-item[data-item-id="${itemId}"]`);
                            
                            if ($item.length) {
                                if (isAccessible) {
                                    $item.removeClass('locked');
                                    $item.attr('data-accessible', '1');
                                    const $icon = $item.find('.curriculum-item-icon');
                                    if ($icon.length) {
                                        if (type === 'lesson') {
                                            $icon.html('<i class="fas fa-play"></i>');
                                        } else if (type === 'quiz') {
                                            $icon.html('<i class="fas fa-comments"></i>');
                                        } else {
                                            $icon.html('<i class="fas fa-file-alt"></i>');
                                        }
                                    }
                                    const $status = $item.find('.curriculum-item-status');
                                    if ($status.find('.fa-lock').length) {
                                        $status.html('<div class="status-circle"></div>');
                                    }
                                } else {
                                    $item.addClass('locked');
                                    $item.attr('data-accessible', '0');
                                    const $icon = $item.find('.curriculum-item-icon');
                                    if ($icon.length) {
                                        $icon.html('<i class="fas fa-lock"></i>');
                                    }
                                }
                            }
                        });
                    }
                }
            },
            error: function(xhr) {
            }
        });
    }

    let searchTimeout;
    $(document).on('input', '#search-input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();

        if (query.length < 2) {
            $('#search-results').hide().empty();
            return;
        }

        searchTimeout = setTimeout(function() {
            $.ajax({
                url: appUrl(`student/courses/${courseId}/search`),
                method: 'POST',
                data: {
                    q: query,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success && response.results.length > 0) {
                        const resultsHtml = response.results.map(function(result) {
                            return `<div class="search-result-item p-2 border-bottom" 
                                    data-item-id="${result.id}" 
                                    data-item-type="${result.type}" 
                                    style="cursor: pointer;">
                                <strong>${result.title}</strong> (${result.type}) - ${result.topic}
                            </div>`;
                        }).join('');
                        $('#search-results').html(resultsHtml).show();
                    } else {
                        $('#search-results').html('<div class="p-2">No results found</div>').show();
                    }
                }
            });
        }, 500);
    });

    $(document).on('click', '.search-result-item', function() {
        const itemId = $(this).data('item-id');
        const itemType = $(this).data('item-type');
        loadItem(itemId, itemType, courseId);
        $('#search-results').hide();
        $('#search-input').val('');
    });

    if (typeof currentNavigation !== 'undefined') {
        updateNavigationButtons(currentNavigation);
    }

    if (currentItemType === 'lesson') {
        setTimeout(function() {
            initializeVideoPlayer();
        }, 500);
    } else if (currentItemType === 'quiz') {
        if ($('#quiz-form').length) {
            setTimeout(function() {
                initializeQuizTimer();
            }, 500);
        }
    }
});

// Global Forum State Variables
let currentForumFilter = 'all';
let currentForumSearch = '';

// Load Discussions
function loadDiscussions(filter = 'all', search = '') {
    currentForumFilter = filter;
    currentForumSearch = search;
    
    $('#forum-container').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Loading discussions...</p>
        </div>
    `);

    $.ajax({
        url: appUrl(`student/courses/${courseId}/discussions`),
        method: 'GET',
        data: {
            filter: filter,
            search: search
        },
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
            } else {
                $('#forum-container').html(`<div class="alert alert-danger">${response.error || 'Failed to load discussions'}</div>`);
            }
        },
        error: function(xhr) {
            $('#forum-container').html(`<div class="alert alert-danger">Error fetching discussions. Please try again.</div>`);
        }
    });
}

// Filter and Search Triggers
function loadDiscussionsFiltered(filter) {
    loadDiscussions(filter, currentForumSearch);
}

function searchForum(event) {
    event.preventDefault();
    const searchVal = $('#forum-search-input').val();
    loadDiscussions(currentForumFilter, searchVal);
}

// Create new discussion thread
function submitNewDiscussion(event) {
    event.preventDefault();
    const form = $('#new-discussion-form');
    const submitBtn = form.find('button[type="submit"]');
    submitBtn.prop('disabled', true).text('Posting...');

    $.ajax({
        url: appUrl(`student/courses/${courseId}/discussions`),
        method: 'POST',
        data: form.serialize(),
        success: function(response) {
            if (response.success) {
                // Reset form and collapse
                form[0].reset();
                $('#askQuestionForm').removeClass('show');
                $('#forum-container').html(response.html);
                if (window.toastMagic) {
                    window.toastMagic.success('Discussion Thread Posted!', response.message);
                } else {
                    alert(response.message);
                }
            } else {
                alert(response.error || 'Failed to post discussion');
            }
        },
        error: function(xhr) {
            const err = xhr.responseJSON ? xhr.responseJSON.error : 'An error occurred';
            alert(err);
        },
        complete: function() {
            submitBtn.prop('disabled', false).text('Post Thread');
        }
    });
}

// Show a discussion thread's details
function showDiscussion(discussionId) {
    $('#forum-container').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Loading discussion details...</p>
        </div>
    `);

    $.ajax({
        url: appUrl(`student/courses/${courseId}/discussions/${discussionId}`),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
            }
        },
        error: function() {
            $('#forum-container').html('<div class="alert alert-danger">Failed to load discussion details.</div>');
        }
    });
}

// Submit reply
function submitReply(event, discussionId) {
    event.preventDefault();
    const form = $('#new-reply-form');
    const submitBtn = form.find('button[type="submit"]');
    submitBtn.prop('disabled', true).text('Posting...');

    $.ajax({
        url: appUrl(`student/courses/${courseId}/discussions/${discussionId}/replies`),
        method: 'POST',
        data: form.serialize(),
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
                if (window.toastMagic) {
                    window.toastMagic.success('Success', response.message);
                }
            }
        },
        error: function(xhr) {
            alert('Failed to post reply. Please try again.');
        },
        complete: function() {
            submitBtn.prop('disabled', false).html('<i class="fas fa-reply me-1"></i> Post Reply');
        }
    });
}

// Toggle likes
function toggleLike(discussionId, event) {
    event.stopPropagation();
    const btn = $(event.currentTarget);
    btn.prop('disabled', true);

    $.ajax({
        url: appUrl(`student/courses/${courseId}/discussions/${discussionId}/like`),
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                if (response.liked) {
                    btn.addClass('liked');
                } else {
                    btn.removeClass('liked');
                }
                btn.find('.like-count').text(response.likes_count);
            }
        },
        complete: function() {
            btn.prop('disabled', false);
        }
    });
}

function toggleReplyLike(replyId, event) {
    const btn = $(event.currentTarget);
    btn.prop('disabled', true);

    $.ajax({
        url: appUrl(`student/courses/${courseId}/discussions/replies/${replyId}/like`),
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                if (response.liked) {
                    btn.addClass('liked');
                } else {
                    btn.removeClass('liked');
                }
                btn.find('.like-count').text(response.likes_count);
            }
        },
        complete: function() {
            btn.prop('disabled', false);
        }
    });
}

// Delete discussion thread
function deleteDiscussion(discussionId, event) {
    event.stopPropagation();
    if (!confirm('Are you sure you want to delete this discussion thread? All replies will be permanently deleted.')) {
        return;
    }

    $.ajax({
        url: appUrl(`student/courses/${courseId}/discussions/${discussionId}`),
        method: 'POST',
        data: {
            _method: 'DELETE',
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
                if (window.toastMagic) {
                    window.toastMagic.success('Success', response.message);
                }
            }
        },
        error: function(xhr) {
            alert('Failed to delete discussion.');
        }
    });
}

// Delete reply
function deleteReply(discussionId, replyId, event) {
    if (!confirm('Are you sure you want to delete this reply?')) {
        return;
    }

    $.ajax({
        url: appUrl(`student/courses/${courseId}/discussions/${discussionId}/replies/${replyId}`),
        method: 'POST',
        data: {
            _method: 'DELETE',
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
                if (window.toastMagic) {
                    window.toastMagic.success('Success', response.message);
                }
            }
        },
        error: function() {
            alert('Failed to delete reply.');
        }
    });
}

// AJAX Pagination Click Handler
$(document).on('click', '.forum-pagination a', function(e) {
    e.preventDefault();
    let url = $(this).attr('href');
    
    $('#forum-container').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Loading discussions...</p>
        </div>
    `);

    $.ajax({
        url: url,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#forum-container').html(response.html);
                // Scroll to top of forum container
                $('#forum-view-wrapper').animate({ scrollTop: 0 }, 'slow');
            }
        }
    });
});


