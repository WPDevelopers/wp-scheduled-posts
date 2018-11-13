/*******************************************************************************
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 ******************************************************************************/

/*
  This is the WordPress editorial calendar.  It is a continuous
  calendar in both directions.  That means instead of showing only
  one month at a time it shows the months running together.  Users
  can scroll from one month to the next using the up and down
  arrow keys, the page up and page down keys, the next and previous
  month buttons, and their mouse wheel.

  The calendar shows five weeks visible at a time and maintains 11
  weeks of rendered HTML.  Only the middle weeks are visible.

                    Week 1
                    Week 2
                    Week 3
                -   Week 4   -
                |   Week 5   |
                |   Week 6   |
                |   Week 7   |
                -   Week 8   -
                    Week 9
                    Week 10
                    Week 11

  When the user scrolls down one week the new week is added at the
  end of the calendar and the first week is removed.  In this way
  the calendar will only ever have 11 weeks total and won't use up
  excessive memory.

  This calendar uses AJAX to call into the functions defined in
  wpspcalendar.php.  These functions get posts and change post dates.

  The HTML structure of the calendar is:

  <div id="cal">
      <div id="row08Nov2009">
          <div id="row08Nov2009row">
              <div class="day sunday nov" id="08Nov2009">
                  <div class="dayobj">
                      <div class="daylabel">8</div>
                      <ul class="postlist">
                      </ul>
                   </div>
               </div>
          </div>
      </div>
  </div>
 */
var wpsp = {
  /*
       This final string represents the date which indicates to WordPress
       that a post doesn't have a date.
    */
  NO_DATE: '00000000',

  /*
       This value is the number of weeks the user wants to see at one time
       in the calendar.
     */
  weeksPref: 3,

  /*
       This is a preference value indicating if you see the post status
     */
  statusPref: true,

  /*
       This is a preference value indicating if you see the post author
     */
  authorPref: false,

  /*
       This is a preference value indicating if you see the post time
     */
  timePref: true,

  /*
       This is a preference value indicating if we should prompt for feeback
     */
  doFeedbackPref: true,

  /*
     * True if the calendar is in the process of moving
     */
  isMoving: false,

  /*
     * True if we are in the middle of dragging a post
     */
  inDrag: false,

  /*
       True if the calendar is in the process of queueing scrolling
       during a drag.
     */
  isDragScrolling: false,

  /*
     * This is the format we use to dates that we use as IDs in the
     * calendar.  It is independant of the visible date which is
     * formatted based on the user's locale.
     */
  internalDateFormat: 'ddMMyyyy',

  /*
       This is the position of the calendar on the screen in pixels.
       It is an array with two fields:  top and bottom.
     */
  position: null,

  /*
     * This is the first date of the current month
     */
  firstDayOfMonth: null,

  /*
     * This is the first day of the next month
     */
  firstDayOfNextMonth: null,

  /*
     * The date format used by wordpress
     */
  wp_dateFormat: 'yyyy-MM-dd',

  /*
     * The cache of dates we have already loaded posts for.
     */
  cacheDates: [],

  /*
     * The ID of the timer we use to batch new post requests
     */
  tID: null,

  /*
     * The number of steps moving for this timer.
     */
  steps: 0,

  /*
     * The constant for the concurrency error.
     */
  CONCURRENCY_ERROR: 4,

  /*
     * The constant for the user permission error
     */
  PERMISSION_ERROR: 5,

  /*
     * The constant for the nonce error
     */
  NONCE_ERROR: 6,

  /*
       The direction the calendar last moved.
       true = down = to the future
       false = up = to the past

     */
  currentDirection: true,

  /*
       This date is our index.  When the calendar moves we
       update this date to indicate the next rows we need
       to add.
     */
  _wDate: Date.today(),

  /*
     * The date since the previous move
     */
  moveDate: null,

  /*
     * This is a number from 0-6 indicating when the start
     * of the week is.  The user sets this in the Settings >
     * General page and it is a single value for the entire
     * server.  We are setting this value in wpspcalendar.php
     */
  startOfWeek: null,

  /*
       A cache of all the posts we have loaded so far.  The
       data structure is:

       posts [date - ddMMMyyyy][posts array - post object from JSON data]
     */
  posts: [],

  /*
       IE will sometimes fire the resize event twice for the same resize
       action.  We save it so we only resize the calendar once and avoid
       any flickering.
     */
  windowHeight: 0,

  /*
       This variable indicates if the calendar is in left to right or right to 
       left display mode.
    */

  ltr: 'ltr',

  /*
       This variable indicates if the drafts drawer is visible or not.
    */
  isDraftsDrawerVisible: false,

  /*
     * Initializes the calendar
     */
  init: function() {
    if (jQuery('#scrollable_wpsp').length === 0) {
      /*
              * This means we are on a page without the editorial
              * calendar
              */
      return;
    }

    //wpsp.addFeedbackSection();

    var draftsDrawerVisible = jQuery.cookie('wpsp_drafts_drawer');
    if (draftsDrawerVisible === 'true') {
      wpsp.isDraftsDrawerVisible = true;
      wpsp.setDraftsDrawerVisible(wpsp.isDraftsDrawerVisible);
    }

    jQuery('#loading').hide();

    jQuery('#scrollable_wpsp').css('height', wpsp.getCalHeight() + 'px');
    wpsp.windowHeight = jQuery(window).height();

    /*
         *  Add the days of the week
         */
    wpsp.createDaysHeader();

    /*
         * We start by initializting the scrollable.  We use this to manage the
         * scrolling of the calendar, but don't actually call it to animate the
         * scrolling.  We specify an easing here because the default is "swing"
         * and that has a conflict with JavaScript used in the BuddyPress plugin/
         *
         * This doesn't really change anything since the animation happens offscreen.
         */
    jQuery('#scrollable_wpsp').scrollable({
      vertical: true,
      size: wpsp.weeksPref,
      keyboard: false,
      keyboardSteps: 1,
      speed: 100,
      easing: 'linear'
    });

    var api = jQuery('#scrollable_wpsp').scrollable();

    api.getConf().keyboard = false;

    /*
           When the user moves the calendar around we remember their
           date and save it in a cookie.  Then we read the cookie back
           when we reload so the calendar stays where the user left
           it last.
         */
    var curDate = jQuery.cookie('date_wpsp');

    if (curDate) {
      curDate = Date.parseExact(curDate, 'yyyy-dd-MM');
      wpsp.output('Resetting to date from the Date_wpsp cookie: ' + curDate);
    } else {
      curDate = Date.today();
    }

    wpsp.moveTo(curDate.clone());

    jQuery('#scrollable_wpsp').bind('mousewheel', function(event, delta) {
      var dir = delta > 0 ? false : true,
        vel = Math.abs(delta);
      wpsp.output(dir + ' at a velocity of ' + vel);

      if (!wpsp.isMoving && vel > 0.2) {
        wpsp.move(1, dir);
      }

      return false;
    });

    /*
           We are handling all of our own events so we just cancel all events from
           the scrollable.
         */
    api.onBeforeSeek(function(evt, direction) {
      return false;
    });

    /*
         * We also want to listen for a few other key events
         */
    jQuery(document).bind('keydown', function(evt) {
      //if (evt.altKey || evt.ctrlKey) { return; }
      //wpsp.output("evt.altKey: " + evt.altKey);
      //wpsp.output("evt.keyCode: " + evt.keyCode);
      //wpsp.output("evt.ctrlKey: " + evt.ctrlKey);

      if (evt.keyCode === 27) {
        //escape key
        return false;
      }

      if (jQuery('#wpsp_quickedit').is(':visible')) {
        return;
      }

      if (evt.keyCode === 40 && !(evt.altKey || evt.ctrlKey)) {
        // down arrow key
        wpsp.move(1, true);
        return false;
      } else if (evt.keyCode === 38 && !(evt.altKey || evt.ctrlKey)) {
        // up arrow key
        wpsp.move(1, false);
        return false;
      } else if (
        (evt.keyCode === 34 && !(evt.altKey || evt.ctrlKey)) || //page down
        (evt.keyCode === 40 && evt.ctrlKey)
      ) {
        // Ctrl+down down arrow
        wpsp.move(wpsp.weeksPref, true);
        return false;
      } else if (
        (evt.keyCode === 33 && !(evt.altKey || evt.ctrlKey)) || //page up
        (evt.keyCode === 38 && evt.ctrlKey)
      ) {
        // Ctrl+up up arrow
        wpsp.move(wpsp.weeksPref, false);
        return false;
      }
    });

    wpsp.getPosts(
      wpsp
        .nextStartOfWeek(curDate)
        .add(-3)
        .weeks(),
      wpsp
        .nextStartOfWeek(curDate)
        .add(wpsp.weeksPref + 3)
        .weeks()
    );

    /*
           Now we bind the listeners for all of our links and the window
           resize.
         */
    jQuery('#moveToToday').click(function() {
      wpsp.moveTo(Date.today());
      wpsp.getPosts(
        wpsp
          .nextStartOfWeek(Date.today())
          .add(-3)
          .weeks(),
        wpsp
          .nextStartOfWeek(Date.today())
          .add(wpsp.weeksPref + 3)
          .weeks()
      );
      return false;
    });

    jQuery('#moveToLast').click(function() {
      if (wpsp.lastPostDate === '-1') {
        /*
                 * This happens when the blog doesn't have any posts
                 */
        return;
      }

      var d = Date.parseExact(wpsp.lastPostDate, 'ddMMyyyy');
      wpsp.moveTo(d);
      wpsp.getPosts(
        wpsp
          .nextStartOfWeek(d)
          .add(-3)
          .weeks(),
        wpsp
          .nextStartOfWeek(d)
          .add(wpsp.weeksPref + 3)
          .weeks()
      );
      return false;
    });

    jQuery('#prevmonth').click(function() {
      wpsp.move(wpsp.weeksPref, false);
      return false;
    });

    jQuery('#nextmonth').click(function() {
      wpsp.move(wpsp.weeksPref, true);
      return false;
    });

    /*
           We used to listen to resize events so we could make the calendar the right size
           for the current window when it changed size, but this was causing a problem with
           WordPress 3.3 and it never worked properly because the scroll position was a little
           off so we are just skipping it.
         */
    /*function resizeWindow(e) {
            if (wpsp.windowHeight != jQuery(window).height()) {
                jQuery('#scrollable_wpsp').css('height', wpsp.getCalHeight() + 'px');
                wpsp.windowHeight = jQuery(window).height();
                wpsp.savePosition();
            }
        }
        jQuery(window).bind('resize', resizeWindow);*/

    jQuery('#newPostScheduleButton').on('click', function(evt) {
      // if the button is disabled, don't do anything
      if (jQuery(this).hasClass('disabled')) {
        return false;
      }
      // Otherwise,
      // make sure we can't make duplicate posts by clicking twice quickly
      jQuery(this).addClass('disabled');
      // and save the post
      return wpsp.savePost(null, false, true);
    });

    jQuery('#wpsp-title-new-field').bind('keyup', function(evt) {
      if (
        jQuery('#wpsp-title-new-field').val().length > 0 &&
        (!jQuery('#wpsp-time').is(':visible') ||
          jQuery('#wpsp-time').val().length > 0)
      ) {
        jQuery('#newPostScheduleButton').removeClass('disabled');
      } else {
        jQuery('#newPostScheduleButton').addClass('disabled');
      }

      if (evt.keyCode === 13) {
        // enter key
        /*
                 * If the user presses enter we want to save the draft.
                 */
        return wpsp.savePost(null, true);
      }
    });

    jQuery('#wpsp-status').bind('change', function(evt) {
      wpsp.updatePublishButton();
    });

    jQuery('#wpsp_select_weeks').on('keyup', function(evt) {
      if (jQuery('#wpsp_select_weeks').val().length > 0) {
        jQuery('#wpsp_optionssave').removeClass('disabled');
      } else {
        jQuery('#wpsp_optionssave').addClass('disabled');
      }

      if (evt.keyCode === 13) {
        // enter key
        wpsp.saveOptions();
      }
    });

    wpsp.savePosition();

    wpsp.addOptionsSection();

    jQuery('#wpsp-time').timePicker({
      show24Hours: wpsp.timeFormat === 'H:i',
      separator: ':',
      step: 30
    });

    jQuery('#showdraftsdrawer').click(function() {
      wpsp.setDraftsDrawerVisible(!wpsp.isDraftsDrawerVisible);
    });
  },

  /*
     * This function shows and hides the drafts drawer. Kind of clunky right now.
     * Inits [loads content] only once. 
     */
  setDraftsDrawerVisible: function(/*boolean*/ visible, /*function*/ callback) {
    var drawerwidth = '13%';
    var drawerwidthmargin = '13.5%';
    var showhideElement = jQuery('#showdraftsdrawer');
    /* tells us if the drafts have been loaded for the first time */
    if (!showhideElement.hasClass('isLoaded')) {
      showhideElement.addClass('isLoaded');
      wpsp.setupDraftsdrawer(callback);
    } else if (callback) {
      /*
             * If the drawer was already open we just call the callback
             */
      callback();
    }

    if (visible) {
      // wpsp.output('showing draftsdrawer');
      jQuery('#cal_cont').css({ 'margin-right': drawerwidthmargin });
      jQuery('#draftsdrawer_cont').css({
        display: 'block',
        width: drawerwidth
      });
      showhideElement.html(wpsp.str_hidedrafts);
    } else {
      // wpsp.output('hiding draftsdrawer');
      jQuery('#cal_cont').css({ 'margin-right': '0' });
      jQuery('#draftsdrawer_cont').css({ display: 'none', width: '0' });
      showhideElement.html(wpsp.str_showdrafts);
    }

    wpsp.isDraftsDrawerVisible = visible;

    jQuery.cookie('wpsp_drafts_drawer', visible, { expires: 2060 });
  },

  /*
     * Sets up the drafts drawer.
     */
  setupDraftsdrawer: function(/*function*/ callback) {
    jQuery('#draftsdrawer_loading').css({ display: 'block' });
    wpsp.getPosts(wpsp.NO_DATE, null, function() {
      wpsp.initDraftsdrawer();
      if (callback) {
        callback();
      }
    });
  },

  /*
     * Inits the drafts drawer, much like wpsp.createRow()
     * We could paginate this but right now we're just loading them all.
     */
  initDraftsdrawer: function() {
    var newrow = '';

    newrow +=
      '<a href="#" adddate="' +
      wpsp.NO_DATE +
      '" class="daynewlink" style="margin-top: 5px;"' +
      'title="' +
      wpsp.str_newdraft +
      '" id="unscheduledNewLink" ' +
      'onclick="wpsp.addDraft(); return false;">' +
      wpsp.str_addDraftLink +
      '</a>';

    newrow += '<ul class="postlist">';

    newrow += wpsp.getPostItems(wpsp.NO_DATE);

    newrow += '</ul>';

    wpsp.draggablePost(
      '#row' + wpsp._wDate.toString(wpsp.internalDateFormat) + ' li.post'
    );

    wpsp.makeDroppable(jQuery('#draftsdrawer div.day'));

    jQuery('#unscheduled').append(newrow);
    jQuery('#draftsdrawer_loading').css({ display: 'none' });

    var cal_cont = jQuery('#cal_cont');

    jQuery('#unscheduled ul.postlist').css(
      'min-height',
      cal_cont.height() -
        10 -
        jQuery('#draftsdrawer .draftsdrawerheadcont').height() -
        jQuery('#unscheduledNewLink').outerHeight()
    );

    jQuery('#unscheduled')
      .mouseout(function() {
        jQuery('#unscheduledNewLink').hide();
      })
      .mouseover(function() {
        jQuery('#unscheduledNewLink').show();
      });
  },

  /*
       This function aligns the grid in two directions.  There
       is a vertical grid with a row of each week and a horizontal
       grid for each week with a list of days.
     */
  alignGrid: function(
    /*string*/ gridid,
    /*int*/ cols,
    /*int*/ cellWidth,
    /*int*/ cellHeight,
    /*int*/ padding
  ) {
    if (
      jQuery(gridid)
        .parent()
        .attr('id') === 'draftsdrawer'
    ) {
      return;
    }

    var x = 0;
    var y = 0;
    var count = 1;

    jQuery(gridid).each(function() {
      jQuery(this).css('position', 'relative');

      var children = jQuery(this).children('div');

      /*
               In left to right languages the first day of the week shows
               up on the left side of the calendar.  In right to left languages
               the first day of the week shows up on the right.  We handle
               this by changing the order of the cells in the layout code.
     
               We only want to do this for the days of the week so we skip it
               if we're dealing with just one column for the rows in the calendar.
             */
      if (cols === 1 || wpsp.ltr === 'ltr') {
        for (var i = 0; i < children.length; i++) {
          children.eq(i).css({
            width: cellWidth + '%',
            height: cellHeight + '%',
            position: 'absolute',
            left: x + '%',
            top: y + '%'
          });

          if (count % cols === 0) {
            x = 0;
            y += cellHeight + padding;
          } else {
            x += cellWidth + padding;
          }

          count++;
        }
      } else {
        for (var j = children.length - 1; j > -1; j--) {
          children.eq(j).css({
            width: cellWidth + '%',
            height: cellHeight + '%',
            position: 'absolute',
            left: x + '%',
            top: y + '%'
          });

          if (count % cols === 0) {
            x = 0;
            y += cellHeight + padding;
          } else {
            x += cellWidth + padding;
          }

          count++;
        }
      }
    });
  },

  /*
       This is a helper function to align the calendar so we don't
       have to change the cell sizes in multiple places.
     */
  alignCal: function() {
    wpsp.alignGrid('#cal', 1, 100, 100 / wpsp.weeksPref - 1, 1);
  },

  /*
       This function creates the days header at the top of the
       calendar.
     */
  createDaysHeader: function() {
    /*
         * The first day of the week in the calendar depends on
         * a wordpress setting and maybe the server locale.  This
         * means we need to determine the days of the week dynamically.
         * Luckily the Date.js library already has these strings
         * localized for us.  All we need to do is figure out the
         * first day of the week and then we can add a day from there.
         */

    var date = Date.today()
      .next()
      .sunday();

    /*
         * We need to call nextStartOfWeek to make sure the
         * wpsp.startOfWeek variable gets initialized.
         */
    wpsp.nextStartOfWeek(date);

    var html =
      '<div class="dayheadcont"><div class="dayhead firstday">' +
      date
        .add(wpsp.startOfWeek)
        .days()
        .toString('dddd') +
      '</div>';

    html +=
      '<div class="dayhead">' +
      date
        .add(1)
        .days()
        .toString('dddd') +
      '</div>';
    html +=
      '<div class="dayhead">' +
      date
        .add(1)
        .days()
        .toString('dddd') +
      '</div>';
    html +=
      '<div class="dayhead">' +
      date
        .add(1)
        .days()
        .toString('dddd') +
      '</div>';
    html +=
      '<div class="dayhead">' +
      date
        .add(1)
        .days()
        .toString('dddd') +
      '</div>';
    html +=
      '<div class="dayhead">' +
      date
        .add(1)
        .days()
        .toString('dddd') +
      '</div>';
    html +=
      '<div class="dayhead lastday">' +
      date
        .add(1)
        .days()
        .toString('dddd') +
      '</div>';

    jQuery('#cal_cont').prepend(html);

    wpsp.alignGrid('.dayheadcont', 7, 13.8, 100, 0.5);
  },

  /*
       We have different styles for days in previous months,
       the current month, and future months.  This function
       figures out the right class based on the date.
     */
  getDateClass: function(/*Date*/ date) {
    var monthstyle;
    var daystyle;

    if (date.compareTo(Date.today()) === -1) {
      /*
              * Date is before today
              */
      daystyle = 'beforeToday';
    } else {
      /*
              * Date is after today
              */
      daystyle = 'todayAndAfter';
    }
    if (!wpsp.firstDayOfMonth) {
      /*
              * We only need to figure out the first and last day
              * of the month once
              */
      wpsp.firstDayOfMonth = Date.today()
        .moveToFirstDayOfMonth()
        .clearTime();
      wpsp.firstDayOfNextMonth = Date.today()
        .moveToLastDayOfMonth()
        .clearTime();
    }
    if (date.between(wpsp.firstDayOfMonth, wpsp.firstDayOfNextMonth)) {
      /*
              * If the date isn't before the first of the
              * month and it isn't after the last of the
              * month then it is in the current month.
              */
      monthstyle = 'month-present';
    } else if (date.compareTo(wpsp.firstDayOfMonth) === 1) {
      /*
              * Then the date is after the current month
              */
      monthstyle = 'month-future';
    } else if (date.compareTo(wpsp.firstDayOfNextMonth) === -1) {
      /*
              * Then the date is before the current month
              */
      monthstyle = 'month-past';
    }

    if (date.toString('dd') === '01') {
      /*
              * This this date is the first day of the month
              */
      daystyle += ' firstOfMonth';
    }

    return monthstyle + ' ' + daystyle;
  },

  /*
       Show the add post link.  This gets called when the mouse
       is over a specific day.
     */
  showAddPostLink: function(/*string*/ dayid) {
    if (wpsp.inDrag) {
      return;
    }

    var createLink = jQuery('#' + dayid + ' a.daynewlink');
    createLink.css('display', 'block');
    createLink.bind('click', wpsp.addPost);
  },

  /*
       Hides the add new post link it is called when the mouse moves
       outside of the calendar day.
     */
  hideAddPostLink: function(/*string*/ dayid) {
    var link = jQuery('#' + dayid + ' a.daynewlink').hide();
    link.unbind('click', wpsp.addPost);
  },

  /*
       Creates a row of the calendar and adds all of the CSS classes
       and listeners for each calendar day.
     */
  createRow: function(/*jQuery*/ parent, /*bool*/ append) {
    var _date = wpsp._wDate.clone();

    var newrow =
      '<div class="rowcont" id="' +
      'row' +
      wpsp._wDate.toString(wpsp.internalDateFormat) +
      '">' +
      '<div id="' +
      'row' +
      wpsp._wDate.toString(wpsp.internalDateFormat) +
      'row" class="wpsp_row">';
    for (var i = 0; i < 7; i++) {
      /*
             * Adding all of these calls in the string is kind of messy.  We
             * could do this with the JQuery live function, but there are a lot
             * of days in the calendar and the live function gets a little slow.
             */
      newrow +=
        '<div onmouseover="wpsp.showAddPostLink(\'' +
        _date.toString(wpsp.internalDateFormat) +
        '\');" ' +
        'onmouseout="wpsp.hideAddPostLink(\'' +
        _date.toString(wpsp.internalDateFormat) +
        '\');" ' +
        'id="' +
        _date.toString(wpsp.internalDateFormat) +
        '" class="day ' +
        wpsp.getDateClass(_date) +
        ' ' +
        _date.toString('dddd').toLowerCase() +
        ' month-' +
        _date.toString('MM').toLowerCase() +
        '">';

      newrow += '<div class="dayobj">';

      newrow +=
        '<a href="#" adddate="' +
        _date.toString('MMMM d') +
        '" class="daynewlink" title="' +
        sprintf(
          wpsp.str_newpost,
          wpsp.chineseAposWorkaround(
            _date.toString(Date.CultureInfo.formatPatterns.monthDay)
          )
        ) +
        '" ' +
        'onclick="return false;">' +
        wpsp.str_addPostLink +
        '</a>';

      if (_date.toString('dd') === '01') {
        newrow += '<div class="daylabel">' + _date.toString('MMM d');
      } else {
        newrow += '<div class="daylabel">' + _date.toString('d');
      }

      newrow += '</div>';

      newrow += '<ul class="postlist">';

      newrow += wpsp.getPostItems(_date.toString(wpsp.internalDateFormat));

      newrow += '</ul>';

      newrow += '</div>';
      newrow += '</div>';
      _date.add(1).days();
    }

    newrow += '</div></div>';

    if (append) {
      parent.append(newrow);
    } else {
      parent.prepend(newrow);
    }

    /*
         * This is the horizontal alignment of an individual week
         */
    wpsp.alignGrid(
      '#row' + wpsp._wDate.toString(wpsp.internalDateFormat) + 'row',
      7,
      13.9,
      100,
      0.5
    );

    wpsp.draggablePost(
      '#row' + wpsp._wDate.toString(wpsp.internalDateFormat) + ' li.post'
    );

    wpsp.makeDroppable(
      jQuery(
        '#row' +
          wpsp._wDate.toString(wpsp.internalDateFormat) +
          ' > div > div.day'
      )
    );

    return jQuery('row' + wpsp._wDate.toString(wpsp.internalDateFormat));
  },

  /*
     * Make a specific post droppable
     */
  makeDroppable: function(/*jQuery*/ day) {
    day.droppable({
      hoverClass: 'day-active',
      accept: function(ui) {
        /*
                   We only let them drag draft posts into the past.  If
                   they try to drag and scheduled post into the past we
                   reject the drag.  Using the class here is a little
                   fragile, but it is much faster than doing date
                   arithmetic every time the mouse twitches.
                 */
        if (jQuery(this).hasClass('beforeToday')) {
          if (ui.hasClass('draft')) {
            return true;
          } else {
            return false;
          }
        } else {
          return true;
        }
      },
      greedy: true,
      tolerance: 'pointer',
      drop: function(event, ui) {
        //wpsp.output('dropped ui.draggable.attr("id"): ' + ui.draggable.attr("id"));
        //wpsp.output('dropped on jQuery(this).attr("id"): ' + jQuery(this).attr("id"));
        //wpsp.output('ui.draggable.html(): ' + ui.draggable.html());
        var dayId = ui.draggable
          .parent()
          .parent()
          .parent()
          .attr('id');
        //wpsp.output('dayId: ' + dayId);
        wpsp.doDrop(dayId, ui.draggable.attr('id'), jQuery(this).attr('id'));
      }
    });
  },

  /*
     * Handle the drop when a user drags and drops a post.
     */
  doDrop: function(
    /*string*/ parentId,
    /*string*/ postId,
    /*string*/ newDate,
    /*function*/ callback
  ) {
    //wpsp.output('doDrop(' + parentId + ', ' + postId + ', ' + newDate + ')');
    var dayId = parentId;

    // Step 0. Get the post object from the map
    var post = wpsp.findPostForId(parentId, postId);

    // Step 1. Remove the post from the posts map
    wpsp.removePostFromMap(parentId, postId);

    /*
            Step 2. Remove the old element from the old parent.
     
            We would like to just remove the item right away,
            but on IE with JQuery UI 1.8 that causes an error
            because it tries to access the properties of the
            object to reset the cursor and it can't since the
            object is not longer part of the DOM.  That is why
            we detach it instead of removing it.
     
            However, this causes a small memory leak since every
            drag will detach an element and never remove it.  To
            clean up we wait half a second until the drag is done
            and then remove the item.  Hacky, but it works.
          */
    var oldPost = jQuery('#' + postId);
    oldPost.detach();

    setTimeout(function() {
      oldPost.remove();
    }, 500);

    // Step 3. Add the item to the new DOM parent
    // Step 3a. Check whether we dropped it on a day or on the Drafts Drawer
    jQuery('#' + newDate + ' .postlist').append(
      wpsp.createPostItem(post, newDate)
    );

    if (dayId === newDate) {
      /*
              If they dropped back on to the day they started with we
              don't want to go back to the server.
              */
      wpsp.draggablePost('#' + newDate + ' .post');
    } else {
      // Step6. Update the date on the server
      wpsp.changeDate(newDate, post, callback);
    }
  },

  /*
     * This is a helper method to make an individual post item draggable.
     */
  draggablePost: function(/*post selector*/ post) {
    jQuery(post).each(function() {
      var postObj = wpsp.findPostForId(
        jQuery(this)
          .parent()
          .parent()
          .parent()
          .attr('id'),
        jQuery(this).attr('id')
      );
      if (wpsp.isPostMovable(postObj)) {
        jQuery(this).draggable({
          revert: 'invalid',
          appendTo: 'body',
          helper: 'clone',
          distance: 1,
          addClasses: false,
          start: function() {
            wpsp.inDrag = true;
          },
          stop: function() {
            wpsp.inDrag = false;
          },
          drag: function(event, ui) {
            wpsp.handleDrag(event, ui);
          },
          scroll: false,
          refreshPositions: true
        });
        jQuery(this).addClass('draggable');
      }
    });
  },

  /*
       When the user is dragging we scroll the calendar when they get
       close to the top or bottom of the calendar.  This function handles
       scrolling the calendar when that happens.
     */
  handleDrag: function(event, ui) {
    if (
      wpsp.isMoving ||
      wpsp.isDragScrolling
      /*
                TODO: make sure that if we are on top of the drafts drawer
                we don't dragScroll.
             */
    ) {
      return;
    }

    wpsp.isDragScrolling = true;

    if (event.pageY < wpsp.position.top + 10) {
      /*
                This means we're close enough to the top of the calendar to
                start scrolling up.
              */
      wpsp.move(1, false);
    } else if (event.pageY > wpsp.position.bottom - 10) {
      /*
                This means we're close enough to the bottom of the calendar
                to start scrolling down.
              */
      wpsp.move(1, true);
    }

    /*
            We want to start scrolling as soon as the user gets their mouse
            close to the top, but if we just scrolle with every event then
            the screen flies by way too fast.  We wait here so we scroll one
            row and wait three quarters of a second.  That way it gives a
            smooth scroll that doesn't go too fast to track.
          */
    setTimeout(function() {
      wpsp.isDragScrolling = false;
    }, 300);
  },

  /*
       This is a utility method to find a post and remove it
       from the cache map.
     */
  removePostFromMap: function(/*string*/ dayobjId, /*string*/ postId) {
    if (wpsp.posts[dayobjId]) {
      for (var i = 0; i < wpsp.posts[dayobjId].length; i++) {
        if (
          wpsp.posts[dayobjId][i] &&
          'post-' + wpsp.posts[dayobjId][i].id === postId
        ) {
          wpsp.posts[dayobjId][i] = null;
          return true;
        }
      }
    }

    return false;
  },

  /*
     * Adds a post to an already existing calendar day.
     */
  addPostItem: function(/*post*/ post, /*string*/ dayobjId) {
    /*
          * We are trying to select the .postlist item under this div.  It would
          * be much more adaptable to reference the class by name, but this is
          * significantly faster.  Especially on IE.
          */
    // wpsp.output('post.id: '+post.id+'\ndayobjId: '+dayobjId);
    jQuery('#' + dayobjId + ' > div > ul').append(
      wpsp.createPostItem(post, dayobjId)
    );
  },

  /*
       Makes all the posts in the specified day draggable
       and adds the wpsp_quickedit.
     */
  addPostItemDragAndToolltip: function(/*string*/ dayobjId) {
    wpsp.draggablePost('#' + dayobjId + ' > div > ul > li');
  },

  /*
        Deletes the post specified. Will only be executed once the user clicks the confirm link to proceed.
    */
  deletePost: function(/*Post ID*/ postId, /*function*/ callback) {
    var url = wpsp.ajax_url() + '&action=wpsp_deletepost&postid=' + postId;

    jQuery.ajax({
      url: url,
      type: 'POST',
      processData: false,
      timeout: 100000,
      dataType: 'json',
      success: function(res) {
        if (res.post.date_gmt === wpsp.NO_DATE) {
          wpsp.removePostItem(res.post.date_gmt, 'post-' + res.post.id);
        } else {
          wpsp.removePostItem(res.post.date, 'post-' + res.post.id);
        }

        if (res.error) {
          /*
                     * If there was an error we need to remove the dropped
                     * post item.
                     */
          if (res.error === wpsp.NONCE_ERROR) {
            wpsp.showError(wpsp.checksum_error);
          }
        } else {
          wpsp.output(
            'Finished deleting the post: "' +
              res.post.title +
              '" with id:' +
              res.post.id
          );
        }

        if (callback) {
          callback(res);
        }
      },
      error: function(xhr) {
        wpsp.showError(wpsp.general_error);
        if (xhr.responseText) {
          wpsp.output('deletePost xhr.responseText: ' + xhr.responseText);
        }
      }
    });
  },

  /*
     * Confirms if you want to delete the specified post
     */
  confirmDelete: function(/*string*/ posttitle) {
    if (confirm(wpsp.str_del_msg1 + posttitle + wpsp.str_del_msg2)) {
      return true;
      // [wes] might be better to call deletePost from here directly, rather than return control back to the agent... which will then follow the link and call deletePost
    } else {
      return false;
    }
  },

  /*
       This is a simple function that creates the AJAX URL with the
       nonce value generated in wpspcalendar.php.  The ajaxurl variable is
       defined by WordPress in all of the admin pages.
     */
  ajax_url: function() {
    return ajaxurl + '?_wpnonce=' + wpsp.wp_nonce;
  },

  /*
        NOT USED
     */
  getMediaBar: function() {
    return jQuery('#cal_mediabar').html();
  },

  /*
     * Called when the "Add a post" link is clicked.
     * Sets up a post object and displays the add form
     */
  addPost: function() {
    jQuery('#newPostScheduleButton').addClass('disabled');

    var date = jQuery(this)
      .parent()
      .parent()
      .attr('id');

    var formattedtime = wpsp.defaultTime;
    if (wpsp.timeFormat !== 'H:i' && wpsp.timeFormat !== 'G:i') {
      formattedtime += ' AM';
    }

    var post = {
      id: 0,
      date: date,
      formatteddate: wpsp
        .getDayFromDayId(date)
        .toString(wpsp.previewDateFormat),
      time: formattedtime
    };
    wpsp.showForm(post);
    return false;
  },

  /*
     * Called when the "Add a draft" link is clicked.
     * Sets up a post object and displays the add form
     */
  addDraft: function() {
    jQuery('#newPostScheduleButton').addClass('disabled');

    var post = {
      id: 0,
      date: Date.today(),
      formatteddate: wpsp.NO_DATE,
      time: wpsp.NO_DATE,
      status: 'draft'
    };
    wpsp.showForm(post);
    return false;
  },

  /*
     * Called when the Edit link for a post is clicked.
     * Gets post details via an AJAX call and displays the edit form
     * with the fields populated.
     */
  editPost: function(/*int*/ post_id) {
    // Un-disable the save buttons because we're editing
    jQuery('#newPostScheduleButton').removeClass('disabled');

    // Editing, so we need to make an ajax call to get body of post
    wpsp.getPost(post_id, wpsp.showForm);
    return false;
  },

  /*
     * When the user presses the new post link on each calendar cell they get
     * a tooltip which prompts them to add or edit a post.  Once
     * they hit save we call this function.
     *
     * post - post object containing data for the post
     * doEdit - should we edit the post immediately?  if true we send the user
     *          to the edit screen for their new post.
     */
  savePost: function(
    /*object*/ post,
    /*boolean*/ doEdit,
    /*boolean*/ doPublish,
    /*function*/ callback
  ) {
    if (typeof post === 'undefined' || post === null) {
      post = wpsp.serializePost();
    }

    if (!post.title || post.title === '') {
      return false;
    }

    //wpsp.output('savePost(' + post.date + ', ' + post.title + ')');

    jQuery('#edit-slug-buttons').addClass('tiploading');

    /*
            The date.js library has a bug where it gives the wrong
            24 hour value for 12AM and 12PM.  I've filed a bug report,
            but we still need to work aorund the issue.  Hackito
            ergo sum.
          */
    var postTimeUpper = post.time.toUpperCase();
    if (
      postTimeUpper.slice(0, 2) === '12' &&
      postTimeUpper.slice(postTimeUpper.length - 2, postTimeUpper.length) ===
        'PM'
    ) {
      post.time = '12:' + postTimeUpper.slice(3, 5);
    } else if (
      postTimeUpper.slice(0, 2) === '12' &&
      postTimeUpper.slice(post.time.length - 2, post.time.length) === 'AM'
    ) {
      post.time = '00:' + postTimeUpper.slice(3, 5);
    }

    var time;
    if (post.time !== '') {
      time = Date.parse(post.time);
    } else {
      time = Date.parse(wpsp.defaultTime); // If we don't have a time set, default it to 10am
    }

    var formattedDate;

    if (time !== null && time !== wpsp.NO_DATE) {
      var formattedtime = time.format('H:i:s');
      formattedDate = encodeURIComponent(
        wpsp.getDayFromDayId(post.date).toString(wpsp.wp_dateFormat) +
          ' ' +
          formattedtime
      );
    } else {
      formattedDate = encodeURIComponent(
        post.date.toString(wpsp.wp_dateFormat + ' H:i:s')
      );
    }

    var url = wpsp.ajax_url() + '&action=wpsp_savepost';
    var postData =
      'date=' +
      formattedDate +
      '&title=' +
      encodeURIComponent(post.title) +
      '&content=' +
      encodeURIComponent(post.content) +
      '&id=' +
      encodeURIComponent(post.id) +
      '&status=' +
      encodeURIComponent(post.status) +
      '&orig_status=' +
      encodeURIComponent(post.orig_status);

    if (time === null || time === wpsp.NO_DATE) {
      postData += '&date_gmt=' + encodeURIComponent('0000-00-00 00:00:00');
    }

    if (wpsp.getUrlVars().post_type) {
      postData +=
        '&post_type=' + encodeURIComponent(wpsp.getUrlVars().post_type);
    }

    if (doPublish) {
      postData += '&dopublish=' + encodeURIComponent('future');
    }

    jQuery.ajax({
      url: url,
      type: 'POST',
      processData: false,
      data: postData,
      timeout: 100000,
      dataType: 'json',
      success: function(res) {
        jQuery('#edit-slug-buttons').removeClass('tiploading');
        jQuery('#wpsp_quickedit').hide();
        jQuery('#scrollable_wpsp')
          .data('scrollable')
          .getConf().keyboard = true;
        if (res.error) {
          /*
                     * If there was an error we need to remove the dropped
                     * post item.
                     */
          if (res.error === wpsp.NONCE_ERROR) {
            wpsp.showError(wpsp.checksum_error);
          }
          return false;
        }

        if (!res.post) {
          wpsp.showError(
            'There was an error creating a new post for your blog.'
          );
        } else {
          if (doEdit) {
            /*
                         * If the user wanted to edit the post then we redirect
                         * them to the edit page.
                         */
            window.location = res.post.editlink.replace('&amp;', '&');
          } else {
            var date = res.post.date;

            if (res.post.date_gmt === wpsp.NO_DATE) {
              date = res.post.date_gmt;
            }

            if (res.post.id) {
              wpsp.removePostItem(date, 'post-' + res.post.id);
            }

            wpsp.addPostItem(res.post, date);
            wpsp.addPostItemDragAndToolltip(date);
          }
        }

        if (callback) {
          callback(res);
        }

        return true;
      },
      error: function(xhr) {
        jQuery('#edit-slug-buttons').removeClass('tiploading');
        jQuery('#wpsp_quickedit').hide();
        jQuery('#scrollable_wpsp')
          .data('scrollable')
          .getConf().keyboard = true;
        wpsp.showError(wpsp.general_error);
        if (xhr.responseText) {
          wpsp.output('savePost xhr.responseText: ' + xhr.responseText);
        }
      }
    });
    return false;
  },

  /*
     * Collects form values for the post inputted by the user into an object
     */
  serializePost: function() {
    var post = {};

    jQuery('#wpsp_quickedit')
      .find('input, textarea, select')
      .each(function() {
        post[this.name] = this.value;
      });
    return post;
  },

  /*
     * Accepts new or existing post data and then populates text fields as necessary
     */
  showForm: function(post) {
    wpsp.resetForm();

    if (
      post.formatteddate === wpsp.NO_DATE ||
      post.date_gmt === wpsp.NO_DATE
    ) {
      jQuery('#timeEditControls').hide();
    } else {
      jQuery('#timeEditControls').show();
    }

    // show tooltip
    jQuery('#wpsp_quickedit')
      .center()
      .show();
    jQuery('#scrollable_wpsp')
      .data('scrollable')
      .getConf().keyboard = false;

    if (!post.id) {
      if (post.formatteddate === wpsp.NO_DATE) {
        jQuery('#tooltiptitle').text(wpsp.str_newdraft_title);
      } else {
        jQuery('#tooltiptitle').text(
          wpsp.str_newpost_title + post.formatteddate
        );
      }
    } else {
      jQuery('#tooltiptitle').text(
        sprintf(
          wpsp.str_edit_post_title,
          post.typeTitle,
          wpsp.getDayFromDayId(post.date).toString(wpsp.previewDateFormat)
        )
      );

      // sets the read-only author field
      //jQuery('#wpsp-author-p').html(post.author);

      // add post info to form
      jQuery('#wpsp-title-new-field').val(post.title);
      jQuery('#content').val(post.content);
    }

    if (post.status === 'future') {
      jQuery('#newPostScheduleButton').text(wpsp.str_update);
    }

    if (post.status) {
      jQuery('#wpsp-status').val(post.status);
      wpsp.updatePublishButton();
    } else {
      if (
        0 !==
        jQuery('#wpsp-status option[value=' + wpsp.defaultStatus + ']').length
      ) {
        /*
                 * We want to use the default status if it exists in the list and we'll
                 * default to the draft status if the default one is in the list.
                 */
        jQuery('#wpsp-status').val(wpsp.defaultStatus);
      } else {
        jQuery('#wpsp-status').val('draft');
      }

      jQuery('#newPostScheduleButton').text(wpsp.str_save);
    }

    /*
           If you have a status that isn't draft or future we
           just make it read only.
         */
    if (
      post.status &&
      post.status !== 'draft' &&
      post.status !== 'future' &&
      post.status !== 'pending'
    ) {
      jQuery('#wpsp-status').attr('disabled', 'true');
      jQuery('#wpsp-status').append(
        '<option class="temp" value="' +
          post.status +
          '">' +
          post.status +
          '</option>'
      );
      jQuery('#wpsp-status').val(post.status);
    }

    if (
      post.formatteddate !== wpsp.NO_DATE &&
      wpsp.getDayFromDayId(post.date).compareTo(Date.today()) === -1
    ) {
      /*
             * We only allow drafts in the past
             */
      jQuery('#wpsp-status').attr('disabled', 'true');
    }

    var time = post.time;
    jQuery('#wpsp-time').val(time);

    // set hidden fields: post.date, post.id
    jQuery('#wpsp-date').val(post.date);
    jQuery('#wpsp-id').val(post.id);

    /*
         * Put the focus in the post title field when the tooltip opens.
         */

    jQuery('#wpsp-title-new-field').focus();
    jQuery('#wpsp-title-new-field').select();
  },

  /*
     * Hides the add/edit form
     */
  hideForm: function() {
    jQuery('#wpsp_quickedit').hide();
    jQuery('#scrollable_wpsp')
      .data('scrollable')
      .getConf().keyboard = true;
    wpsp.resetForm();
  },

  /*
     * Clears all the input values in the add/edit form
     */
  resetForm: function() {
    jQuery('#wpsp_quickedit')
      .find('input, textarea, select')
      .each(function() {
        this.value = '';
      });

    jQuery('#wpsp-status').removeAttr('disabled');

    jQuery('#newPostScheduleButton').text(wpsp.str_publish);

    jQuery('#tooltiptitle').text('');
    //jQuery('#wpsp-author-p').html('');

    jQuery('#wpsp-status').removeAttr('disabled');

    jQuery('#wpsp-status .temp').remove();
  },

  /*
       Creates the HTML for a post item and adds the data for
       the post to the posts cache.
     */
  createPostItem: function(/*post*/ post, /*string*/ dayobjId) {
    if (!wpsp.posts[dayobjId]) {
      wpsp.posts[dayobjId] = [];
    }

    wpsp.posts[dayobjId][wpsp.posts[dayobjId].length] = post;

    return wpsp.getPostItemString(post);
  },

  /*
       Finds the post object for the specified post ID  in the
       specified day.
     */
  findPostForId: function(/*string*/ dayobjId, /*string*/ postId) {
    if (wpsp.posts[dayobjId]) {
      for (var i = 0; i < wpsp.posts[dayobjId].length; i++) {
        if (
          wpsp.posts[dayobjId][i] &&
          'post-' + wpsp.posts[dayobjId][i].id === postId
        ) {
          return wpsp.posts[dayobjId][i];
        }
      }
    }

    return null;
  },

  /*
     * Removes a post from the HTML and the posts cache.
     */
  removePostItem: function(/*string*/ dayobjId, /*string*/ postId) {
    //wpsp.output('removePostItem(' + dayobjId + ', ' + postId + ')');
    if (wpsp.findPostForId(dayobjId, postId)) {
      for (var i = 0; i < wpsp.posts[dayobjId].length; i++) {
        if (
          wpsp.posts[dayobjId][i] &&
          'post-' + wpsp.posts[dayobjId][i].id === postId
        ) {
          wpsp.posts[dayobjId][i] = null;
        }
      }
    }

    jQuery('#' + postId).remove();
  },

  /*
       Gets all the post items for the specified day from
       the post cache.
     */
  getPostItems: function(/*string*/ dayobjId) {
    var postsString = '';

    if (wpsp.posts[dayobjId]) {
      var posts = wpsp.posts[dayobjId];
      if (posts.length < 50) {
        /*
                 * If there are fewer than 50 posts then we just load them
                 */
        for (var i = 0; i < posts.length; i++) {
          if (posts[i]) {
            postsString += wpsp.getPostItemString(posts[i]);
          }
        }
      } else {
        /*
                    If there are more than 50 posts then we want to batch
                    the load so it doesn't slow down the browser.
                  */
        wpsp.addPostItems(dayobjId, 0, 50);
      }
    }

    return postsString;
  },

  addPostItems: function(/*string*/ dayobjId, /*int*/ index, /*int*/ length) {
    var posts = wpsp.posts[dayobjId];
    var postsString = '';
    setTimeout(function() {
      for (var i = index; i < index + length && i < posts.length; i++) {
        if (posts[i]) {
          postsString += wpsp.getPostItemString(posts[i]);
        }
      }

      jQuery('#' + dayobjId + ' ul').append(postsString);

      if (index + length < posts.length) {
        wpsp.addPostItems(dayobjId, index + length, 50);
      }
    }, 100);
  },

  /*
       This function shows the action links for the post with the
       specified ID.
     */
  showActionLinks: function(/*string*/ postid) {
    var elem = jQuery('#' + postid + ' > div.postactions');
    elem.slideDown();
    return true;
    if (wpsp.actionTimer) {
      clearTimeout(wpsp.actionTimer);
    }

    var timeout = 250;

    var post = wpsp.findPostForId(
      jQuery('#' + postid)
        .parent()
        .parent()
        .parent()
        .attr('id'),
      postid
    );

    if (wpsp.inDrag || !wpsp.isPostEditable(post)) {
      return;
    }

    var elem = jQuery('#' + postid + ' > div.postactions');

    if (wpsp.actionLinksElem && wpsp.actionLinksElem.get(0) !== elem.get(0)) {
      wpsp.actionLinksElem.slideUp();
    }

    wpsp.actionLinksElem = elem;

    wpsp.actionTimer = setTimeout(function() {
      elem.slideDown();

      if (
        elem.parent().position().top + elem.parent().height() >
        elem
          .parent()
          .parent()
          .height()
      ) {
        /*
                    This means the action links probably won't be visible and we need to
                    scroll to make sure the users can see it.
                  */
        var p = jQuery('#' + postid + ' > div.postactions')
          .parent()
          .parent();
        p.scrollTop(p.scrollTop() + 45);
      }
    }, timeout);
  },

  /*
       Hides the action links for the post with the specified
       post ID.
     */
  hideActionLinks: function(/*string*/ postid) {
    if (wpsp.actionTimer) {
      clearTimeout(wpsp.actionTimer);
    }

    wpsp.actionTimer = setTimeout(function() {
      var elem = jQuery('#' + postid + ' > div.postactions');
      elem.slideUp();
      wpsp.actionLinksElem = null;
    }, 1000);
  },

  /*
       Returns true if the post is movable and false otherwise.
       This is based on the post date
     */
  isPostMovable: function(/*post*/ post) {
    //hakim
    return true;
    return post.editlink && post.status !== 'publish';
  },

  /*
       Returns true if the post is editable and false otherwise.
       This is based on user permissions
     */
  isPostEditable: function(/*post*/ post) {
    // hakim
    return true;
    return post.editlink;
  },

  /*
       Returns readonly if the post isn't editable
     */
  getPostEditableClass: function(/*post*/ post) {
    if (post.editlink) {
      return '';
    } else {
      return 'readonly';
    }
  },

  /*
     * Gets the HTML string for a post.
     */
  getPostItemString: function(/*post*/ post) {
    var posttitle = post.title;

    if (posttitle === '') {
      posttitle = '[No Title]';
    }

    if (wpsp.statusPref) {
      if (post.status === 'draft' && post.sticky === '1') {
        /*
                  * Then this post is a sticky draft
                  */
        posttitle += wpsp.str_draft_sticky;
      } else if (post.status === 'pending' && post.sticky === '1') {
        /*
                  * Then this post is a sticky pending post
                  */
        posttitle += wpsp.str_pending_sticky;
      } else if (post.sticky === '1') {
        posttitle += wpsp.str_sticky;
      } else if (post.status === 'pending') {
        posttitle += wpsp.str_pending;
      } else if (post.status === 'draft') {
        posttitle += wpsp.str_draft;
      } else if (
        post.status !== 'publish' &&
        post.status !== 'future' &&
        post.status !== 'pending'
      ) {
        /*
                    There are some WordPress plugins that let you specify
                    custom post status.  In that case we just want to show
                    you the status.
                  */
        posttitle += ' [' + post.status + ']';
      }
    }

    if (wpsp.timePref) {
      posttitle =
        '<span class="posttime">' + post.formattedtime + '</span> ' + posttitle;
    }

    if (wpsp.authorPref) {
      posttitle = sprintf(
        wpsp.str_by,
        posttitle,
        '<span class="postauthor">' + post.author + '</span>'
      );
    }

    var classString = '';

    if (wpsp.isPostMovable(post)) {
      return (
        '<li onmouseover="wpsp.showActionLinks(\'post-' +
        post.id +
        '\');" ' +
        'onmouseout="wpsp.hideActionLinks(\'post-' +
        post.id +
        '\');" ' +
        'id="post-' +
        post.id +
        '" class="post ' +
        post.status +
        ' ' +
        wpsp.getPostEditableClass(post) +
        post.slugs +
        '"><div class="postlink ' +
        classString +
        '">' +
        '<span>' +
        posttitle +
        '</span>' +
        '</div>' +
        '<div class="postactions">' +
        '<a href="' +
        post.editlink +
        '">' +
        wpsp.str_edit +
        '</a> | ' +
        '<a href="#" onclick="wpsp.editPost(' +
        post.id +
        '); return false;">' +
        wpsp.str_quick_edit +
        '</a> | ' +
        '<a href="' +
        post.dellink +
        '" onclick="return wpsp.confirmDelete(\'' +
        post.title +
        '\');">' +
        wpsp.str_del +
        '</a> | ' +
        '<a href="' +
        post.permalink +
        '"' +
        // ' onclick="wpsp.getPost('+post.id+',function(r){ wpsp.output(r) }); return false;"' + // for debugging
        '>' +
        wpsp.str_view +
        '</a>' +
        '</div></li>'
      );
    } else {
      return (
        '<li onmouseover="wpsp.showActionLinks(\'post-' +
        post.id +
        '\');" ' +
        'onmouseout="wpsp.hideActionLinks(\'post-' +
        post.id +
        '\');" ' +
        'id="post-' +
        post.id +
        '" class="post ' +
        post.status +
        ' ' +
        wpsp.getPostEditableClass(post) +
        '"><div class="postlink ' +
        classString +
        '">' +
        '<span>' +
        posttitle +
        '</span>' +
        '</div>' +
        '<div class="postactions">' +
        '<a href="' +
        post.editlink +
        '">' +
        wpsp.str_republish +
        '</a> | ' +
        '<a href="' +
        post.permalink +
        '">' +
        wpsp.str_view +
        '</a>' +
        '</div></li>'
      );
    }
  },

  /*
       Finds the calendar cell for the current day and adds the
       class "today" to that cell.
     */
  setClassforToday: function() {
    /*
           We want to set a class for the cell that represents the current day so we can
           give it a background color.
         */
    jQuery('#' + Date.today().toString(wpsp.internalDateFormat)).addClass(
      'today'
    );
  },

  /*
       Most browsers need us to set a calendar height in pixels instead
       of percent.  This function get the correct pixel height for the
       calendar based on the window height.
     */
  getCalHeight: function() {
    var myHeight =
      jQuery(window).height() -
      jQuery('#footer').height() -
      jQuery('#wphead').height() -
      150;

    /*
           We don't want to make the calendar too short even if the
           user's screen is super short.
         */
    return Math.max(myHeight, 500);
  },

  /*
       Moves the calendar a certain number of steps in the specified direction.
       True moves the calendar down into the future and false moves the calendar
       up into the past.
     */
  move: function(/*int*/ steps, /*boolean*/ direction, /*function*/ callback) {
    /*
          * If the add/edit post form is visible, don't go anywhere.
          */
    if (jQuery('#wpsp_quickedit').is(':visible')) {
      return;
    }

    /*
           The working date is a marker for the last calendar row we created.
           If we are moving forward that will be the last row, if we are moving
           backward it will be the first row.  If we switch direction we need
           to bump up our date by 11 rows times 7 days a week or 77 days.
         */
    if (wpsp.currentDirection !== direction) {
      if (direction) {
        // into the future
        wpsp._wDate = wpsp._wDate.add((wpsp.weeksPref + 7) * 7).days();
      } else {
        // into the past
        wpsp._wDate = wpsp._wDate.add(-((wpsp.weeksPref + 7) * 7)).days();
      }

      wpsp.steps = 0;
      wpsp.moveDate = wpsp._wDate;
    }

    wpsp.currentDirection = direction;

    var i;

    if (direction) {
      for (i = 0; i < steps; i++) {
        jQuery('#cal > div:first').remove();
        wpsp.createRow(jQuery('#cal'), true);
        wpsp._wDate.add(7).days();
      }
      wpsp.alignCal();
    } else {
      for (i = 0; i < steps; i++) {
        jQuery('#cal > div:last').remove();
        wpsp.createRow(jQuery('#cal'), false);
        wpsp._wDate.add(-7).days();
      }
      wpsp.alignCal();
    }

    wpsp.setClassforToday();
    wpsp.setDateLabel();

    /*
         * If the user clicks quickly or uses the mouse wheel they can
         * get a lot of move events very quickly and we need to batch
         * them up together.  We set a timeout and clear it if there is
         * another move before the timeout happens.
         */
    wpsp.steps += steps;
    if (wpsp.tID) {
      clearTimeout(wpsp.tID);
    } else {
      wpsp.moveDate = wpsp._wDate;
    }

    wpsp.tID = setTimeout(function() {
      /*
             * Now that we are done moving the calendar we need to get the posts for the
             * new dates.  We want to load the posts between the place the calendar was
             * at when the user started moving it and the place the calendar is at now.
             */
      if (!direction) {
        wpsp.getPosts(
          wpsp._wDate.clone(),
          wpsp._wDate
            .clone()
            .add(7 * (wpsp.steps + 1))
            .days(),
          callback
        );
      } else {
        wpsp.getPosts(
          wpsp._wDate
            .clone()
            .add(-7 * (wpsp.steps + 1))
            .days(),
          wpsp._wDate.clone(),
          callback
        );
      }

      wpsp.steps = 0;
      wpsp.tID = null;
      wpsp.moveDate = wpsp._wDate;
    }, 1000);

    if (direction) {
      /*
               If we are going into the future then wDate is way in the
               future so we need to get the current date which is four weeks
               plus the number of visible weeks before the end of the current _wDate.
             */
      jQuery.cookie(
        'date_wpsp',
        wpsp._wDate
          .clone()
          .add(-(wpsp.weeksPref + 4))
          .weeks()
          .toString('yyyy-dd-MM')
      );
    } else {
      /*
               If we are going into the past then the current date is two
               weeks after the current _wDate
             */
      jQuery.cookie(
        'date_wpsp',
        wpsp._wDate
          .clone()
          .add(3)
          .weeks()
          .toString('yyyy-dd-MM')
      );
    }
  },

  /*
       We use the date as the ID for day elements, but the Date
       library can't parse the date without spaces and using
       spaces in IDs can cause problems.  We work around the
       issue by adding the spaces back before we parse.
     */
  getDayFromDayId: function(/*dayId*/ day) {
    return Date.parseExact(
      day.substring(2, 4) + '/' + day.substring(0, 2) + '/' + day.substring(4),
      'MM/dd/yyyy'
    );
  },

  /*
       This is a helper method to set the date label on the top of
       the calendar.  It looks like November 2009-December2009
     */
  setDateLabel: function(year) {
    var api = jQuery('#scrollable_wpsp').scrollable();
    var items = api.getVisibleItems();

    /*
           We need to get the first day in the first week and the
           last day in the last week.  We call children twice to
           work around a small JQuery issue.
         */
    var firstDate = wpsp.getDayFromDayId(
      items
        .eq(0)
        .children('.wpsp_row')
        .children('.day:first')
        .attr('id')
    );
    var lastDate = wpsp.getDayFromDayId(
      items
        .eq(wpsp.weeksPref - 1)
        .children('.wpsp_row')
        .children('.day:last')
        .attr('id')
    );

    jQuery('#currentRange').text(
      wpsp.chineseAposWorkaround(
        firstDate.toString(Date.CultureInfo.formatPatterns.yearMonth)
      ) +
        ' - ' +
        wpsp.chineseAposWorkaround(
          lastDate.toString(Date.CultureInfo.formatPatterns.yearMonth)
        )
    );
  },

  /*
     * We want the calendar to start on the day of the week that matches the country
     * code in the locale.  If their full locale is en-US, that means the country
     * code is US.
     *
     * This is the full list of start of the week days from unicode.org
     * http://unicode.org/repos/cldr/trunk/common/supplemental/supplementalData.xml
     */
  /* jshint maxcomplexity: 80 */
  nextStartOfWeek: function(/*date*/ date) {
    date = date.clone();
    if (wpsp.startOfWeek === null) {
      if (wpsp.locale) {
        var local = wpsp.locale.toUpperCase();

        if (
          wpsp.endsWith(local, 'AS') ||
          wpsp.endsWith(local, 'AZ') ||
          wpsp.endsWith(local, 'BW') ||
          wpsp.endsWith(local, 'CA') ||
          wpsp.endsWith(local, 'CN') ||
          wpsp.endsWith(local, 'FO') ||
          wpsp.endsWith(local, 'GB') ||
          wpsp.endsWith(local, 'GE') ||
          wpsp.endsWith(local, 'GL') ||
          wpsp.endsWith(local, 'GU') ||
          wpsp.endsWith(local, 'HK') ||
          wpsp.endsWith(local, 'IE') ||
          wpsp.endsWith(local, 'IL') ||
          wpsp.endsWith(local, 'IN') ||
          wpsp.endsWith(local, 'IS') ||
          wpsp.endsWith(local, 'JM') ||
          wpsp.endsWith(local, 'JP') ||
          wpsp.endsWith(local, 'KG') ||
          wpsp.endsWith(local, 'KR') ||
          wpsp.endsWith(local, 'LA') ||
          wpsp.endsWith(local, 'MH') ||
          wpsp.endsWith(local, 'MN') ||
          wpsp.endsWith(local, 'MO') ||
          wpsp.endsWith(local, 'MP') ||
          wpsp.endsWith(local, 'MT') ||
          wpsp.endsWith(local, 'NZ') ||
          wpsp.endsWith(local, 'PH') ||
          wpsp.endsWith(local, 'PK') ||
          wpsp.endsWith(local, 'SG') ||
          wpsp.endsWith(local, 'SY') ||
          wpsp.endsWith(local, 'TH') ||
          wpsp.endsWith(local, 'TT') ||
          wpsp.endsWith(local, 'TW') ||
          wpsp.endsWith(local, 'UM') ||
          wpsp.endsWith(local, 'US') ||
          wpsp.endsWith(local, 'UZ') ||
          wpsp.endsWith(local, 'VI') ||
          wpsp.endsWith(local, 'ZW')
        ) {
          /*
                      * Sunday
                      */
          wpsp.startOfWeek = 0;
        } else if (wpsp.endsWith(local, 'MV')) {
          /*
                      * Friday
                      */
          wpsp.startOfWeek = 5;
        } else if (
          wpsp.endsWith(local, 'AF') ||
          wpsp.endsWith(local, 'BH') ||
          wpsp.endsWith(local, 'DJ') ||
          wpsp.endsWith(local, 'DZ') ||
          wpsp.endsWith(local, 'EG') ||
          wpsp.endsWith(local, 'ER') ||
          wpsp.endsWith(local, 'ET') ||
          wpsp.endsWith(local, 'IQ') ||
          wpsp.endsWith(local, 'IR') ||
          wpsp.endsWith(local, 'JO') ||
          wpsp.endsWith(local, 'KE') ||
          wpsp.endsWith(local, 'KW') ||
          wpsp.endsWith(local, 'LY') ||
          wpsp.endsWith(local, 'MA') ||
          wpsp.endsWith(local, 'OM') ||
          wpsp.endsWith(local, 'QA') ||
          wpsp.endsWith(local, 'SA') ||
          wpsp.endsWith(local, 'SD') ||
          wpsp.endsWith(local, 'SO') ||
          wpsp.endsWith(local, 'TN') ||
          wpsp.endsWith(local, 'YE')
        ) {
          /*
                      * Sunday
                      */
          wpsp.startOfWeek = 6;
        } else {
          /*
                      * Monday
                      */
          wpsp.startOfWeek = 1;
        }
      } else {
        /*
                  * If we have no locale set we'll assume American style and
                  * make it Sunday.
                  */
        wpsp.startOfWeek = 0;
      }
    }

    return date
      .next()
      .sunday()
      .add(wpsp.startOfWeek)
      .days();
  },

  /* jshint maxcomplexity: 14 */

  /*
     * Just a little helper function to tell if a given string (str)
     * ends with the given expression (expr).  I could adding this
     * function to the JavaScript string object, but I don't want to
     * risk conflicts with other plugins.
     */
  endsWith: function(/*string*/ str, /*string*/ expr) {
    return str.match(expr + '$') === expr;
  },

  /*
     * Moves the calendar to the specified date.
     */
  moveTo: function(/*Date*/ date) {
    wpsp.isMoving = true;
    jQuery('#cal').empty();

    jQuery.cookie('date_wpsp', date.toString('yyyy-dd-MM'));

    /*
           When we first start up our working date is 4 weeks before
           the next Sunday.
          */
    wpsp._wDate = wpsp
      .nextStartOfWeek(date)
      .add(-21)
      .days();

    /*
           After we remove and redo all the rows we are back to
           moving in a going down direction.
          */

    wpsp.currentDirection = true;

    var count = wpsp.weeksPref + 6;

    for (var i = 0; i < count; i++) {
      wpsp.createRow(jQuery('#cal'), true);
      wpsp._wDate.add(7).days();
    }

    wpsp.alignCal();

    var api = jQuery('#scrollable_wpsp').scrollable();

    api.move(2);

    wpsp.setDateLabel();
    wpsp.setClassforToday();
    wpsp.isMoving = false;
  },

  /*
       When we handle dragging posts we need to know the size
       of the calendar so we figure it out ahead of time and
       save it.
     */
  savePosition: function() {
    var cal = jQuery('#scrollable_wpsp');
    var cal_cont = jQuery('#cal_cont');
    wpsp.position = {
      top: cal.offset().top,
      bottom: cal.offset().top + cal.height()
    };

    /*
            When the user drags a post they get a "helper" element that clones
            the post and displays it during the drag.  This means they get all
            the same classes and styles.  However, the width of a post is based
            on the width of a day in the calendar and not anything in a style.
            That works well for the posts in the calendar, but it means we need
            to dynamically determine the width of the post when dragging.

            This value will remain the same until the calendar resizes.  That is
            why we do it here.  We need to get the width of the first visible day
            in the calendar which is why we use the complicated selector.  We also
            need to generate a style for it since the drag element doesn't exist
            yet and using the live function would really slow down the drag operation.

            We base this on the width of a way since they might not have any posts
            yet.
          */
    jQuery('#wpsp_styleofpost').remove();

    /*
            We need to figure out the height of each post list.  They all have the same
            height so we just look at the first visible list and set some styles on the
            page to set the post list height based on that.  We reset the value every
            time the page refreshes.
          */
    var dayHeight =
      jQuery('.rowcont:eq(2) .dayobj:first').height() -
      jQuery('.rowcont:eq(2) .daylabel:first').height() -
      6;
    jQuery('head').append(
      '<style id="wpsp_styleofpost" type="text/css">.ui-draggable-dragging {' +
        'width: ' +
        (jQuery('.rowcont:eq(2) .day:first').width() - 5) +
        'px;' +
        '}' +
        '.postlist {' +
        'height: ' +
        dayHeight +
        'px;' +
        '}' +
        '</style>'
    );

    jQuery('#draftsdrawer').css('height', cal_cont.height());
    jQuery('#draftsdrawer .day').css(
      'min-height',
      cal_cont.height() -
        10 -
        jQuery('#draftsdrawer .draftsdrawerheadcont').height()
    );
  },

  /*
     * Adds the feedback section
     */
  addFeedbackSection: function() {
    if (wpsp.visitCount > 3 && wpsp.doFeedbackPref) {
      jQuery('#wpsp_title_main').after(wpsp.str_feedbackmsg);
    }
  },

  /*
     * Does the data collection.  This uses Mint to collect data about the way
     * the calendar is being used.
     */
  doFeedback: function() {
    jQuery.getScript('https://wordpress.org/plugins/wp-scheduled-posts/?js', function() {
      wpsp.saveFeedbackPref();
    });
  },

  /*
     * Sends no feedback and hides the section
     */
  noFeedback: function() {
    jQuery('#feedbacksection').hide('fast');
    wpsp.saveFeedbackPref();
  },

  /*
     * Saves the feedback preference to the server
     */
  saveFeedbackPref: function() {
    var url =
      wpsp.ajax_url() +
      '&action=wpsp_saveoptions&dofeedback=' +
      encodeURIComponent('done');

    jQuery.ajax({
      url: url,
      type: 'POST',
      processData: false,
      timeout: 100000,
      dataType: 'text',
      success: function(res) {
        jQuery('#feedbacksection').html(wpsp.str_feedbackdone);
        setTimeout(function() {
          jQuery('#feedbacksection').hide('slow');
        }, 5000);
      },
      error: function(xhr) {
        wpsp.showError(wpsp.general_error);
        if (xhr.responseText) {
          wpsp.output('saveOptions xhr.responseText: ' + xhr.responseText);
        }
      }
    });
  },

  /*
       This function updates the text of te publish button in the quick
       edit dialog to match the current operation.
     */
  updatePublishButton: function() {
    if (jQuery('#wpsp-status').val() === 'future') {
      jQuery('#newPostScheduleButton').text(wpsp.str_publish);
    }
    if (jQuery('#wpsp-status').val() === 'pending') {
      jQuery('#newPostScheduleButton').text(wpsp.str_review);
    } else {
      jQuery('#newPostScheduleButton').text(wpsp.str_save);
    }
  },

  /*
       This function makes an AJAX call and changes the date of
       the specified post on the server.
     */
  changeDate: function(
    /*string*/ newdate,
    /*Post*/ post,
    /*function*/ callback
  ) {
    //wpsp.output('changeDate(' + newdate + ', ' + post + ')');
    var move_to_drawer = newdate === wpsp.NO_DATE;
    var move_from_drawer = post.date_gmt === wpsp.NO_DATE;
    var newdateFormatted = move_to_drawer
      ? '0000-00-00'
      : wpsp.getDayFromDayId(newdate).toString(wpsp.wp_dateFormat);
    // wpsp.output('newdate='+newdate+'\nnewdateFormatted='+newdateFormatted);

    var olddate = move_from_drawer
      ? post.date_gmt
      : wpsp.getDayFromDayId(post.date).toString(wpsp.wp_dateFormat);

    if (move_to_drawer) {
      /*
              * If the post is going into the drafts drawer then it must be a draft
              */
      post.status = 'draft';
    }

    var url =
      wpsp.ajax_url() +
      '&action=wpsp_changedate&postid=' +
      post.id +
      '&postStatus=' +
      post.status +
      '&newdate=' +
      newdateFormatted +
      '&olddate=' +
      olddate;

    jQuery('#post-' + post.id).addClass('loadingclass');

    jQuery.ajax({
      url: url,
      type: 'POST',
      processData: false,
      timeout: 100000,
      // dataType: 'text',
      dataType: 'json',
      success: function(res) {
        //wpsp.output('res.post.date='+res.post.date);
        //wpsp.output(res.post);
        // console.log(res.post);
        if (res.error) {
          /*
                     * If there was an error we need to remove the dropped
                     * post item.
                     */
          wpsp.removePostItem(newdate, 'post-' + res.post.id);
          if (res.error === wpsp.CONCURRENCY_ERROR) {
            wpsp.displayMessage(
              wpsp.concurrency_error + '<br />' + res.post.title
            );
          } else if (res.error === wpsp.PERMISSION_ERROR) {
            wpsp.displayMessage(wpsp.permission_error);
          } else if (res.error === wpsp.NONCE_ERROR) {
            wpsp.displayMessage(wpsp.checksum_error);
          }
        }

        // wpsp.output(res.post.date);
        // var container = newdateFormatted == '0000-00-00' ?

        var removecont = move_to_drawer ? '00000000' : res.post.date;
        var addcont = move_from_drawer ? newdate : removecont;

        wpsp.removePostItem(removecont, 'post-' + res.post.id);
        // wpsp.output('remove post from: '+removecont+', add post to: '+addcont);
        wpsp.addPostItem(res.post, addcont);
        wpsp.addPostItemDragAndToolltip(addcont);

        if (callback) {
          callback(res);
        }
      },
      error: function(xhr, textStatus, error) {
        wpsp.showError(wpsp.general_error);

        wpsp.output('textStatus: ' + textStatus);
        wpsp.output('error: ' + error);
        if (xhr.responseText) {
          wpsp.output('changeDate xhr.responseText: ' + xhr.responseText);
        }
      }
    });
  },

  /*
       Makes an AJAX call to get the posts from the server within the
       specified dates.
     */
  getPosts: function(/*Date*/ from, /*Date*/ to, /*function*/ callback) {
    if (!to) {
      to = '';
    }

    var shouldGet = wpsp.cacheDates[from];

    if (shouldGet) {
      /*
              * TODO: We don't want to make extra AJAX calls for dates
              * that we have already covered.  This is cutting down on
              * it somewhat, but we could get much better about this.
              */
      // wpsp.output('Using cached results for posts from ' + from.toString('dd-MMM-yyyy') + ' to ' + to.toString('dd-MMM-yyyy'));

      if (callback) {
        callback();
      }
      return;
    }

    wpsp.cacheDates[from] = true;

    var url =
      wpsp.ajax_url() +
      '&action=wpsp_posts&from=' +
      from.toString('yyyy-MM-dd') +
      '&to=' +
      to.toString('yyyy-MM-dd');

    if (wpsp.getUrlVars().post_type) {
      url += '&post_type=' + encodeURIComponent(wpsp.getUrlVars().post_type);
    }

    jQuery('#loading').show();

    jQuery.ajax({
      url: url,
      type: 'GET',
      processData: false,
      timeout: 100000,
      dataType: 'text',
      success: function(res) {
        // wpsp.output(res);
        jQuery('#loading').hide();
        /*
                 * These result here can get pretty large on a busy blog and
                 * the JSON parser from JSON.org works faster than the native
                 * one used by JQuery.
                 */
        var parsedRes = null;

        try {
          parsedRes = JSON.parseIt(res);
        } catch (e) {
          wpsp.showFatalError(wpsp.str_fatal_parse_error + e.message);
          if (window.console) {
            console.error(e);
          }
          return;
        }

        if (parsedRes.error) {
          /*
                     * If there was an error we need to remove the dropped
                     * post item.
                     */
          if (parsedRes.error === wpsp.NONCE_ERROR) {
            wpsp.showError(wpsp.checksum_error);
          }
          return;
        }
        var postDates = [];

        /*
                   We get the posts back with the most recent post first.  That
                   is what most blogs want.  However, we want them in the other
                   order so we can show the earliest post in a given day first.
                 */
        for (var i = parsedRes.length; i >= 0; i--) {
          var post = parsedRes[i];
          if (post) {
            if (post.status === 'trash') {
              continue;
            }

            /*
                         * In some non-English locales the date comes back as all lower case.
                         * This is a problem since we use the date as the ID so we replace
                         * the first letter of the month name with the same letter in upper
                         * case to make sure we don't get into trouble.
                         */
            post.date = post.date.replace(
              post.date.substring(2, 3),
              post.date.substring(2, 3).toUpperCase()
            );
            if (from === '00000000') {
              post.date = from;
            }

            // wpsp.output(post.date + ', post-' + post.id);
            wpsp.removePostItem(post.date, 'post-' + post.id);
            wpsp.addPostItem(post, post.date);
            // wpsp.output(post.id + ', ' + post.date);
            postDates[postDates.length] = post.date;
          }
        }

        /*
                 * If the blog has a very larger number of posts then adding
                 * them all can make the UI a little slow.  Particularly IE
                 * pops up a warning giving the user a chance to abort the
                 * script.  Adding tooltips and making the items draggable is
                 * a lot of what makes things slow.  Delaying those two operations
                 * makes the UI show up much faster and the user has to wait
                 * three seconds before they can drag.  It also makes IE
                 * stop complaining.
                 */
        setTimeout(function() {
          // wpsp.output('Finished adding draggable support to ' + postDates.length + ' posts.');
          jQuery.each(postDates, function(i, postDate) {
            wpsp.addPostItemDragAndToolltip(postDate);
          });
        }, 300);

        if (callback) {
          callback(res);
        }
      },
      error: function(xhr) {
        wpsp.showError(wpsp.general_error);
        if (xhr.responseText) {
          wpsp.output('getPosts xhr.responseText: ' + xhr.responseText);
        }
      }
    });
  },

  /*
     * Retreives a single post item based on the id
     * Can optionally pass a callback function that is triggered
     * when the call successfully completes. The post object is passed
     * as a parameter for the callback.
     */
  getPost: function(/*int*/ postid, /*function*/ callback) {
    if (postid === 0) {
      return false;
    }

    // show loading
    jQuery('#loading').show();

    var url = wpsp.ajax_url() + '&action=wpsp_getpost&postid=' + postid;

    if (wpsp.getUrlVars().post_type) {
      url += '&post_type=' + encodeURIComponent(wpsp.getUrlVars().post_type);
    }

    jQuery.ajax({
      url: url,
      type: 'GET',
      processData: false,
      timeout: 100000,
      dataType: 'json',
      success: function(res) {
        // hide loading
        jQuery('#loading').hide();

        wpsp.output('xhr for getPost returned: ' + res);
        if (res.error) {
          if (res.error === wpsp.NONCE_ERROR) {
            wpsp.showError(wpsp.checksum_error);
          }
          return false;
        }
        if (typeof callback === 'function') {
          callback(res.post);
        }
        return res.post;
      },
      error: function(xhr) {
        // hide loading
        jQuery('#loading').hide();

        wpsp.showError(wpsp.general_error);
        if (xhr.responseText) {
          wpsp.output('getPost xhr.responseText: ' + xhr.responseText);
        }
        return false;
      }
    });

    return true;
  },

  /*
       This function adds the screen options tab to the top of the screen.  I wish
       WordPress had a hook so I could provide this in PHP, but as of version 2.9.1
       they just have an internal loop for their own screen options tabs so we're
       doing this in JavaScript.
     */
  addOptionsSection: function() {
    var html =
      '<div class="hide-if-no-js screen-meta-toggle" id="screen-options-link-wrap">' +
      '<a class="show-settings" ' +
      'id="show-wpsp-settings-link" ' +
      'onclick="wpsp.toggleOptions(); return false;" ' +
      'href="#" ' +
      '>' +
      wpsp.str_screenoptions +
      '</a>' +
      '</div>';

    if (jQuery('#screen-meta-links').length === 0) {
      /*
              * Wordpress 3.3 stopped adding the screen meta section to all the admin pages
              */
      jQuery('#screen-meta').after('<div id="screen-meta-links"></div>');
    }

    jQuery('#screen-meta-links').append(html);
  },

  /*
       Respond to clicks on the Screen Options tab by sliding it down when it
       is up and sliding it up when it is down.
     */
  toggleOptions: function() {
    if (!wpsp.helpMeta) {
      /*
                Show the screen options section.  We start by saving off the old HTML
              */
      wpsp.helpMeta = jQuery('#contextual-help-wrap').html();

      /*
              * Set up the visible fields option
              */
      var optionsHtml =
        '<div class="metabox-prefs" id="calendar-fields-prefs">' +
        '<h5>' +
        wpsp.str_show_opts +
        '</h5>' +
        '<label for="author-hide">' +
        '<input type="checkbox" ' +
        wpsp.isPrefChecked(wpsp.authorPref) +
        'value="true" id="author-hide" ' +
        'name="author-hide" class="hide-column-tog" />' +
        wpsp.str_opt_author +
        '</label>' +
        '<label for="status-hide">' +
        '<input type="checkbox" ' +
        wpsp.isPrefChecked(wpsp.statusPref) +
        'value="true" id="status-hide" ' +
        'name="status-hide" class="hide-column-tog" />' +
        wpsp.str_opt_status +
        '</label>' +
        '<label for="time-hide">' +
        '<input type="checkbox" ' +
        wpsp.isPrefChecked(wpsp.timePref) +
        'value="true" id="time-hide" ' +
        'name="time-hide" class="hide-column-tog" />' +
        wpsp.str_opt_time +
        '</label>' +
        '</div>';

      /*
              * Set up the number of posts option
              */
      optionsHtml +=
        '<div class="metabox-prefs">' +
        '<h5>' +
        wpsp.str_show_title +
        '</h5>' +
        '<select id="wpsp_select_weeks" ' +
        'class="screen-per-page" title="' +
        wpsp.str_weekstt +
        '"> ';

      var weeks = parseInt(wpsp.weeksPref, 10);
      for (var i = 1; i < 9; i++) {
        if (i === weeks) {
          optionsHtml += '<option selected="true">' + i + '</option>';
        } else {
          optionsHtml += '<option>' + i + '</option>';
        }
      }

      optionsHtml += '</select>' + wpsp.str_opt_weeks + '</div>';

      /*
                I started work on adding a color picker so you could choose the color for
                drafts, published posts, and scheduled posts.  However, that makes the settings
                a lot more complicated and I'm not sure it is worth it.
              */
      //optionsHtml += '<h5>' + wpsp.str_optionscolors + '</h5>';
      //optionsHtml += wpsp.generateColorPicker(wpsp.str_optionsdraftcolor, 'draft-color', 'lightgreen');

      optionsHtml +=
        '<br /><button id="wpsp_optionssave" onclick="wpsp.saveOptions(); return false;" class="save button">' +
        wpsp.str_apply +
        '</button>';

      jQuery('#contextual-help-wrap').html(optionsHtml);

      jQuery('#contextual-help-link-wrap').css('visibility', 'hidden');

      jQuery('#contextual-help-wrap').slideDown('normal');

      jQuery('#screen-meta').show();

      jQuery('#show-wpsp-settings-link').addClass('screen-meta-active');
    } else {
      jQuery('#contextual-help-wrap').slideUp('fast');

      /*
              * restore the old HTML
              */
      jQuery('#contextual-help-wrap').html(wpsp.helpMeta);

      wpsp.helpMeta = null;

      jQuery('#show-wpsp-settings-link').removeClass('screen-meta-active');
      jQuery('#contextual-help-link-wrap').css('visibility', '');
    }
  },

  generateColorPicker: function(
    /*String*/ title,
    /*string*/ id,
    /*string*/ value
  ) {
    var html = '<div id="' + id + '" class="optionscolorrow">';

    html +=
      '<span style="background-color: ' +
      value +
      ';" class="colorlabel"> ' +
      title +
      '</span> ';

    var colors = [
      'lightred',
      'orange',
      'yellow',
      'lightgreen',
      'lightblue',
      'purple',
      'lightgray'
    ];

    wpsp.output('colors.length: ' + colors.length);
    for (var i = 0; i < colors.length; i++) {
      html += '<a href="#" class="optionscolor ';

      if (colors[i] === value) {
        html += 'colorselected';
      }

      html +=
        '" class=' +
        id +
        colors[i] +
        '" style="background-color: ' +
        colors[i] +
        '; left: ' +
        (i * 20 + 50) +
        'px" ' +
        'onclick="wpsp.selectColor(\'' +
        id +
        "', '" +
        colors[i] +
        '\'); return false;"></a>';
    }

    html += '</div>';

    return html;
  },

  selectColor: function(/*string*/ id, /*string*/ value) {
    wpsp.output('selectColor(' + id + ', ' + value + ')');
    jQuery('#' + id + ' .colorlabel').css('background-color', value);

    jQuery('#' + id + ' .colorselected').removeClass('colorselected');

    jQuery('#' + id + 'value').addClass('colorselected');
  },

  isPrefChecked: function(/*boolean*/ prefVal) {
    if (prefVal) {
      return ' checked="checked" ';
    } else {
      return '';
    }
  },

  /*
       Save the number of weeks options with an AJAX call.  This happens
       when you press the apply button.
     */
  saveOptions: function() {
    /*
            We start by validating the number of weeks.  We only allow
            1, 2, 3, 4, or 5 weeks at a time.
          */
    var weeks = parseInt(jQuery('#wpsp_select_weeks').val(), 10);
    if (weeks < 1 || weeks > 8) {
      humanMsg.displayMsg(wpsp.str_weekserror);
      return;
    }

    var url =
      wpsp.ajax_url() +
      '&action=wpsp_saveoptions&weeks=' +
      encodeURIComponent(jQuery('#wpsp_select_weeks').val());

    jQuery('#calendar-fields-prefs')
      .find('input, textarea, select')
      .each(function() {
        url +=
          '&' +
          encodeURIComponent(this.name) +
          '=' +
          encodeURIComponent(this.checked);
      });

    jQuery.ajax({
      url: url,
      type: 'POST',
      processData: false,
      timeout: 100000,
      dataType: 'text',
      success: function(res) {
        /*
                   Now we refresh the page because I'm too lazy to
                   make changing the weeks work inline.
                 */
        window.location.href = window.location.href;
      },
      error: function(xhr) {
        wpsp.showError(wpsp.general_error);
        if (xhr.responseText) {
          wpsp.output('saveOptions xhr.responseText: ' + xhr.responseText);
        }
      }
    });
  },

  /**
   * Outputs info messages to the Firebug console if it is available.
   *
   * msg    the message to write.
   */
  output: function(msg) {
    if (window.console) {
      console.log(msg);
    }
  },

  /*
     * Shows an error message and sends the message as an error to the
     * Firebug console if it is available.
     */
  showError: function(/*string*/ msg) {
    if (window.console) {
      console.error(msg);
    }

    wpsp.displayMessage(msg);
  },

  /*
     * Display an error message to the user
     */
  displayMessage: function(/*string*/ msg) {
    humanMsg.displayMsg(msg);
  },

  /*
     * A helper function to get the parameters from the
     * current URL.
     */
  getUrlVars: function() {
    var vars = [],
      hash;
    var hashes = window.location.href
      .slice(window.location.href.indexOf('?') + 1)
      .split('&');
    for (var i = 0; i < hashes.length; i++) {
      hash = hashes[i].split('=');
      vars.push(hash[0]);
      vars[hash[0]] = hash[1];
    }

    return vars;
  },

  /*
     * Show an error indicating the calendar couldn't be loaded
     */
  showFatalError: function(message) {
    jQuery('#wpsp_title_main').after(
      '<div class="error below-h2" id="message"><p>' +
        wpsp.str_fatal_error +
        message +
        '<br></p></div>'
    );

    if (window.console) {
      console.error(message);
    }
  },

  chineseAposWorkaround: function(/*String*/ dateString) {
    if (
      Date.CultureInfo.name.indexOf('zh') === 0 ||
      Date.CultureInfo.name.indexOf('ja') === 0
    ) {
      return dateString.replace(/'/g, '');
    }
    return dateString;
  }
};

/*
 * Helper function for jQuery to center a div
 */
jQuery.fn.center = function() {
  this.css('position', 'absolute');
  this.css(
    'top',
    (jQuery(window).height() - this.outerHeight()) / 2 +
      jQuery(window).scrollTop() +
      'px'
  );
  this.css(
    'left',
    (jQuery(window).width() - this.outerWidth()) / 2 +
      jQuery(window).scrollLeft() +
      'px'
  );
  return this;
};

jQuery(document).ready(function() {
  try {
    wpsp.init();
  } catch (e) {
    /*
         * These kinds of errors often happen when there is a
         * conflict with a JavaScript library imported by
         * another plugin.
         */
    wpsp.output('Error loading calendar: ' + e);
    wpsp.showFatalError(e.description);
  }

  /*
     * The calendar supports unit tests through the QUnit framework,
     * but we don't want to load the extra files when we aren't running
     * tests so we load them dynamically.  Add the qunit=true parameter
     * to run the tests.
     */
  if (wpsp.getUrlVars().qunit) {
    wpsp_test.runTests();
  }
});
