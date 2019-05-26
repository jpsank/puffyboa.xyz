
function toggleDisplay(id) {
    let img = document.querySelector(`div.message[id="${id}"] img`);
    if (img.style.display === "block") {
        img.style.display = "none";
    } else {
        img.style.display = "block";
    }
}

function findGetParameter(parameterName) {
    let result = null;
    let tmp = [];
    location.search
        .substr(1)
        .split("&")
        .forEach(function (item) {
            tmp = item.split("=");
            if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
        });
    return result;
}

function fetchUpdates() {
    const id = ledger.children[0].id;
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `fetch.php?last_id=${id}`);
    xhr.onreadystatechange = () => {if (xhr.readyState === 4 && xhr.status === 200) {
        const response = JSON.parse(xhr.response);
        addMessages(response["result"], true);
        updateTimePassedElems();
    }};
    xhr.send();

    // loops update fetcher every 5 seconds
    setTimeout(fetchUpdates, 5000);
}

function getTimePassed(time) {
    let time_passed = (Date.now() - Date.parse(time))/1000;
    let units = 'seconds';
    if (time_passed > 60) {
        time_passed /= 60;
        units = 'minutes';

        if (time_passed > 60) {
            time_passed /= 60;
            units = 'hours';

            if (time_passed > 24) {
                time_passed /= 24;
                units = 'days';
            }
        }
    }
    time_passed = Math.round(time_passed);
    return [time_passed, units];
}
function updateTimePassed(messageElem) {
    let post_date = messageElem.getAttribute("data-post-date");
    [time_passed, units] = getTimePassed(post_date);
    let newText = `${time_passed} ${units} ago`;

    let time = messageElem.getElementsByClassName("t")[0];
    if (time.textContent !== newText) {
        time.textContent = newText;
        return true;
    } else {
        return false;
    }
}
function updateTimePassedElems() {
    let messageElems = document.getElementsByClassName("message");
    for (let elem of messageElems) {
        let check = updateTimePassed(elem);
        if (check === false) {
            break;
        }
    }
}

function addMessages(messages, top=false) {
    for (const msg of messages) {

        const div = document.createElement('div');
        div.id = msg.id;
        div.setAttribute("data-post-date", msg["post_date"] + " UTC");
        div.classList.add('message');

        const time = document.createElement('p');
        time.classList.add('t');
        div.appendChild(time);

        const message = document.createElement('p');
        message.classList.add('m');
        message.textContent = msg.message;
        div.appendChild(message);

        if (msg["has_attachment"]) {
            const img = document.createElement('img');
            img.src = `uploads/${msg.id}`;
            div.appendChild(img);
        }

        updateTimePassed(div);

        if (top) {
            // adds the new div to the top of the ledger
            ledger.insertBefore(div, ledger.children[0]);
        } else {
            ledger.appendChild(div);
        }
    }
}

function addPagination(num_pages) {
    const div = document.createElement('div');
    div.classList.add('pages');

    let prev_page = current_page-1;
    if (prev_page >= 0) {
        const prevDiv = document.createElement('div');
        const prevA = document.createElement("a");
        prevA.href = `?page=${prev_page+1}`;
        prevA.textContent = "<";

        prevDiv.appendChild(prevA);
        div.appendChild(prevDiv);
    }

    for (let i = current_page-5; i < current_page+6; i++) {
        if (i >= 0 && i < num_pages) {
            if (i === current_page) {
                const currentDiv = document.createElement('div');
                currentDiv.classList.add("current");
                currentDiv.textContent = i+1;
                div.appendChild(currentDiv);
            } else {
                const pageDiv = document.createElement('div');
                if (i < current_page) {
                    pageDiv.classList.add("previous");
                } else if (i > current_page) {
                    pageDiv.classList.add("next");
                }

                const pageA = document.createElement("a");
                pageA.href = `?page=${i+1}`;
                pageA.textContent = i+1;
                pageDiv.appendChild(pageA);

                div.appendChild(pageDiv);
            }
        }
    }

    let next_page = current_page+1;
    if (next_page < num_pages) {
        const nextDiv = document.createElement('div');
        const nextA = document.createElement("a");
        nextA.href = `?page=${next_page+1}`;
        nextA.textContent = ">";

        nextDiv.appendChild(nextA);
        div.appendChild(nextDiv);
    }


    // adds the new div to the bottom of the ledger
    ledger.appendChild(div);
}

function initialFetch() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `fetch.php?page=${current_page}&per_page=${PER_PAGE}`);
    xhr.onreadystatechange = () => {if (xhr.readyState === 4 && xhr.status === 200) {
        const response = JSON.parse(xhr.response);

        addMessages(response["result"]);

        let num_pages = response["metadata"]["num_pages"];
        addPagination(num_pages);
    }};
    xhr.send();

}

const PER_PAGE = 50;

let current_page = findGetParameter("page");
current_page = (current_page)? parseInt(current_page)-1 : 0;

const ledger = document.getElementById('ledger');

initialFetch();

if (current_page === 0) {
    setTimeout(fetchUpdates, 5000);
}
