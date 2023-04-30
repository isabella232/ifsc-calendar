dayjs.extend(window.dayjs_plugin_relativeTime);
dayjs.extend(window.dayjs_plugin_isBetween);

function sort_by_date(event1, event2) {
    let eventDate1 = new Date(event1.start_time);
    let eventDate2 = new Date(event2.start_time);

    if (eventDate1 < eventDate2) {
        return -1;
    }

    if (eventDate1 > eventDate2) {
        return 1;
    }

    return 0;
}

function event_is_streaming(event) {
    const now = dayjs();
    const eventStart = dayjs(event.start_time);

    return eventStart.isBetween(now, now.subtract(3, 'hour'));
}

function pretty_starts_in(event) {
    return dayjs(event.start_time).fromNow();
}

function pretty_started_ago(event) {
    return `Started ${dayjs(event.start_time).fromNow()}`;
}

function pretty_finished_ago(event) {
    return `Streamed ${dayjs(event.start_time).fromNow()}`;
}

function get_upcoming_events(jsonData) {
    const now = new Date();
    const upcomingEvents = jsonData.events.filter((event) => new Date(event.start_time) >= now);

    upcomingEvents.sort(sort_by_date);

    return upcomingEvents;
}

function sort_leagues_by_id(jsonData) {
    let leagues = [];

    jsonData.events.forEach((event) => {
        if (typeof leagues[event.id] === 'undefined') {
            leagues[event.id] = [];
        }

        leagues[event.id].push(event);
    });

    return leagues;
}

const refresh = (async () => {
    const response = await fetch("events/events.json");
    const jsonData = await response.json();
    const upcomingEvents = get_upcoming_events(jsonData);
    const leagues = sort_leagues_by_id(jsonData);
    const now = new Date();
    const leagueTemplate = document.getElementById('ifsc-league');
    const accordion = document.getElementById('accordion');

    leagues.forEach((league) => {
        const clone = leagueTemplate.content.cloneNode(true);

        clone.getElementById('ifsc-league-name').innerHTML = '🥇 ' + league[0].description.replace(/^IFSC -/, '');
        clone.getElementById('ifsc-league-name').setAttribute('data-target', `#collapse_${league[0].id}`);

        clone.getElementById('heading_id').id = `heading_${league[0].id}`;

        clone.getElementById('collapse_n').setAttribute('aria-labelledby', `collapse_${league[0].id}`);
        clone.getElementById('collapse_n').id = `collapse_${league[0].id}`;

        accordion.appendChild(clone);
    });

    let nextEvent = upcomingEvents.at(0);

    if (nextEvent) {
        document.getElementById(`collapse_${nextEvent.id}`).classList.add('show');
    }

    const template = document.getElementById("ifsc-event");
    let liveEvent = null;

    /*
    while (container.lastElementChild) {
        container.removeChild(container.lastElementChild);
    }
     */

    let lastEventFinished = false;

    jsonData.events.forEach((event) => {
        try {
            const clone = template.content.cloneNode(true);

            if (event.poster) {
                clone.getElementById('ifsc-poster').src = event.poster;
            } else {
                clone.getElementById('ifsc-poster').src = 'img/posters/230329_Poster_SEOUL23_thumb.jpg';
            }

            clone.getElementById('ifsc-description').innerText = event.description;
            clone.getElementById('ifsc-name').innerText = `👉 ${event.name}`;

            if (event.stream_url) {
                clone.getElementById('button-stream').href = event.stream_url;
            } else {
                clone.getElementById('button-stream').href = 'https://www.youtube.com/@sportclimbing/streams';
            }

            let status = clone.getElementById('ifsc-status');

            if (event_is_streaming(event)) {
                clone.getElementById('ifsc-starts-in').innerText = `⏰ ${pretty_started_ago(event)}`;
                clone.getRootNode().firstChild.nextSibling.style.backgroundColor = '#f7f7f7';
                status.innerHTML = `🔴 &nbsp; <strong>Live Now</strong>`;
                status.classList.add('text-danger');
                liveEvent = event;

                clone.getRootNode().firstChild.nextSibling.style.opacity = '100%'
                clone.getElementById('button-results').href = `https://ifsc.results.info/#/event/${event.id}`;
                document.getElementById(`collapse_${event.id}`).getElementsByTagName('ul')[0].appendChild(clone);
            } else if (new Date(event.start_time) > now) {
                clone.getElementById('ifsc-starts-in').innerText = `⏰ Starts ${pretty_starts_in(event)}`;

                if (!liveEvent && lastEventFinished) {
                    lastEventFinished = false;
                    status.innerHTML = `🟢 &nbsp; <strong>Next Event</strong>`;
                    status.classList.add('text-success');

                    clone.getRootNode().firstChild.nextSibling.style.backgroundColor = 'rgba(246,245,245,0.4)';
                    clone.getRootNode().firstChild.nextSibling.style.opacity = '100%'
                } else {
                    clone.getRootNode().firstChild.nextSibling.style.opacity = '70%'
                    status.innerHTML = `⌛️ &nbsp; Upcoming`;
                    status.classList.add('text-warning');
                }

                clone.getElementById('button-results').style.display = 'none';
                document.getElementById(`collapse_${event.id}`).getElementsByTagName('ul')[0].appendChild(clone);
            } else {
                clone.getElementById('ifsc-starts-in').innerText = `⏰ ${pretty_finished_ago(event)}`;
                status.innerHTML = `🏁 &nbsp; Finished`;
                status.classList.add('text-danger');

                clone.getRootNode().firstChild.nextSibling.style.opacity = '70%'
                clone.getElementById('button-results').href = `https://ifsc.results.info/#/event/${event.id}`;

                lastEventFinished = true;
                document.getElementById(`collapse_${event.id}`).getElementsByTagName('ul')[0].appendChild(clone);
            }
        } catch (e) {
            console.log(e)
        }
    });
});

(async () => {
    await refresh();
  //  window.setInterval(refresh, 1000 * 60);
})();
