(this.webpackJsonpgiftbox = this.webpackJsonpgiftbox || []).push([
  [0],
  [
    ,
    ,
    ,
    ,
    ,
    function (e, t, n) {
      e.exports = n(18);
    },
    ,
    ,
    ,
    ,
    function (e, t, n) {},
    function (e, t, n) {},
    function (e, t, n) {},
    function (e, t, n) {},
    function (e, t, n) {},
    function (e, t, n) {},
    function (e, t, n) {},
    function (e, t, n) {},
    function (e, t, n) {
      "use strict";
      n.r(t);
      var a = n(0),
        r = n.n(a),
        c = n(4),
        o = n.n(c),
        i = (n(10), n(1)),
        l = n(2),
        u = function (e, t) {
          switch (t.type) {
            case "SET_AFTER":
              return Object(l.a)({}, e, { mode: "after" });
            case "SET_BEFORE":
              return Object(l.a)({}, e, { mode: "before" });
            case "SET_READY":
              return Object(l.a)({}, e, { mode: "ready" });
            case "SET_COUNTDOWN":
              return Object(l.a)({}, e, {
                mode: "countdown",
                timeLeft: t.timeLeft,
              });
            case "SET_SHOW_GIFT":
              return Object(l.a)({}, e, { mode: "show_gift" });
            default:
              return Object(l.a)({}, e, { mode: "" });
          }
        },
        s = function (e, t) {
          switch (t.type) {
            case "UPDATE_GIFT":
              return t.gift;
            default:
              return e;
          }
        },
        m = function (e, t) {
          switch (t.type) {
            case "UPDATE_HISTORY":
              return t.history;
            default:
              return e;
          }
        },
        p = r.a.createContext(),
        f = { timeOnReceivedGift: 30, hostname: "", soundCloudTimeout: 432e5 },
        d = function (e, t) {
          var n = Object(a.useRef)();
          Object(a.useEffect)(
            function () {
              n.current = e;
            },
            [e]
          ),
            Object(a.useEffect)(
              function () {
                if (null !== t) {
                  var e = setInterval(function () {
                    n.current();
                  }, t);
                  return function () {
                    clearInterval(e);
                  };
                }
              },
              [t]
            );
        },
        h = function (e) {
          fetch("".concat(f.hostname, "/app.php/snahp/giftbox/history/"))
            .then(function (e) {
              return e.json();
            })
            .then(function (t) {
              e({ type: "UPDATE_HISTORY", history: t.history });
            });
        },
        _ =
          (n(11),
          function (e, t) {
            var n = t - e.toString().length + 1;
            return Array(+(n > 0 && n)).join("0") + e;
          }),
        E = function () {
          var e = Object(a.useContext)(p),
            t = e.mode,
            n = e.modeDispatch,
            c = Object(a.useState)(t.timeLeft),
            o = Object(i.a)(c, 2),
            l = o[0],
            u = o[1],
            s = (function (e) {
              var t = Math.floor(e / 60),
                n = _(e % 60, 2);
              return [_(Math.floor(t / 60), 2), _(t % 60, 2), n];
            })(l),
            m = Object(i.a)(s, 3),
            f = m[0],
            h = m[1],
            E = m[2];
          return (
            d(function () {
              u(function (e) {
                return e - 1;
              });
            }, 1e3),
            l >= 0
              ? r.a.createElement(
                  "div",
                  { className: "countdown_wrapper" },
                  r.a.createElement(
                    "div",
                    { className: "countdown_content" },
                    r.a.createElement("img", {
                      className: "countdown_image",
                      src: "https://i.imgur.com/N3CubIM.jpg",
                      alt: "",
                    }),
                    r.a.createElement(
                      "div",
                      { className: "countdown_text" },
                      r.a.createElement(
                        "span",
                        { className: "countdown_hours" },
                        f
                      ),
                      r.a.createElement(
                        "span",
                        { className: "countdown_minutes" },
                        h
                      ),
                      r.a.createElement(
                        "span",
                        { className: "countdown_seconds" },
                        E
                      )
                    )
                  )
                )
              : (n({ type: "SET_MAIN" }), null)
          );
        },
        g =
          (n(12),
          function (e) {
            var t = e.type,
              n = (e.title, e.description, e.img_url);
            return (
              void 0 !== t &&
              r.a.createElement(
                "div",
                { className: "received_gift_wrapper" },
                r.a.createElement(
                  "div",
                  { className: "received_gift_content" },
                  r.a.createElement("img", {
                    className: "received_gift_image",
                    src: n,
                    alt: "",
                  })
                )
              )
            );
          }),
        v = function (e) {
          var t = Object(a.useContext)(p),
            n = t.receivedGift,
            c = t.modeDispatch,
            o = Object(a.useState)(f.timeOnReceivedGift),
            l = Object(i.a)(o, 2),
            u = l[0],
            s = l[1];
          return (
            d(function () {
              s(function (e) {
                return e - 1;
              });
            }, 1e3),
            u >= 0
              ? r.a.createElement("div", null, r.a.createElement(g, n))
              : (c({ type: "SET_MAIN" }), null)
          );
        },
        b =
          (n(13),
          function (e) {
            var t = Object(a.useContext)(p),
              n = t.modeDispatch,
              c = t.receivedGiftDispatch,
              o = t.historyDispatch,
              i = function () {
                fetch("".concat(f.hostname, "/app.php/snahp/giftbox/unwrap/"))
                  .then(function (e) {
                    return e.json();
                  })
                  .then(function (e) {
                    c({ type: "UPDATE_GIFT", gift: e.item }),
                      h(o),
                      n({ type: "SET_SHOW_GIFT" });
                  });
              };
            return r.a.createElement(
              "div",
              { className: "unwrapper_body" },
              r.a.createElement(function (e) {
                return r.a.createElement(
                  "div",
                  { className: "unwrapper_content" },
                  r.a.createElement(
                    "button",
                    {
                      onClick: i,
                      type: "button",
                      className: "unwrapper_button",
                    },
                    r.a.createElement("img", {
                      className: "unwrapper_button_image",
                      src: "https://i.imgur.com/B3fsORS.png",
                      alt: "",
                    })
                  )
                );
              }, null)
            );
          }),
        y = function () {
          return r.a.createElement(b, null);
        },
        w =
          (n(14),
          {
            common: {
              img_url: "https://i.imgur.com/voHuM0g.png",
              title: "Common",
            },
            uncommon: {
              img_url: "https://i.imgur.com/1wBSv0g.png",
              title: "Uncommon",
            },
            rare: { img_url: "https://i.imgur.com/3PvLOJb.png", title: "Rare" },
            mythical: {
              img_url: "https://i.imgur.com/Zfq41wH.png",
              title: "Mythical",
            },
            legendary: {
              img_url: "https://i.imgur.com/ANTYU4M.png",
              title: "Legendary",
            },
            immortal: {
              img_url: "https://i.imgur.com/opBAkho.png",
              title: "Immortal",
            },
            arcana: {
              img_url: "https://i.imgur.com/jgx4ofY.png",
              title: "Arcana",
            },
          }),
        O = function (e) {
          var t = e.item_name,
            n = e.streak,
            a = w[t].img_url,
            c = w[t].title;
          return r.a.createElement(
            "div",
            { className: "history_item_wrapper" },
            r.a.createElement("img", {
              className: "history_item_image",
              title: c,
              src: a,
              alt: "",
            }),
            n > 2 &&
              r.a.createElement(
                "span",
                { className: "history_item_multiplier" },
                "x",
                n
              )
          );
        },
        N =
          (n(15),
          function (e) {
            var t = Object(a.useContext)(p),
              n = t.history,
              c = t.historyDispatch,
              o = 0;
            return (
              Object(a.useEffect)(function () {
                h(c);
              }, []),
              r.a.createElement(
                "div",
                { className: "history_item_list_wrapper" },
                r.a.createElement(
                  "div",
                  { className: "history_item_list_content" },
                  n.map(function (e, t) {
                    return (
                      "common" === e.item_name ? (o += 1) : (o = 0),
                      r.a.createElement(O, {
                        key: t,
                        streak: o,
                        item_name: e.item_name,
                      })
                    );
                  })
                )
              )
            );
          }),
        j =
          (n(16),
          function () {
            return r.a.createElement(
              r.a.Fragment,
              null,
              r.a.createElement("img", {
                className: "event_over_image",
                src: "https://i.imgur.com/Aj6kXQe.gif",
                alt: "",
              }),
              r.a.createElement(
                "p",
                null,
                "Thank you for participating in the"
              ),
              r.a.createElement(
                "p",
                null,
                "4 ",
                r.a.createElement("span", null, "2019"),
                " Christmas Event 4"
              ),
              r.a.createElement("p", null, "We wish you a"),
              r.a.createElement("p", null, "111 Very Merry Christmas 111"),
              r.a.createElement("p", null, "&"),
              r.a.createElement("p", null, "999 Happy New Year 999"),
              r.a.createElement(
                "p",
                null,
                "000 See you in ",
                r.a.createElement("span", null, "2020!"),
                " 000"
              )
            );
          }),
        T = function (e) {
          return r.a.createElement(
            "div",
            { className: "event_over_wrapper" },
            r.a.createElement(
              "span",
              { className: "event_over_content" },
              r.a.createElement(j, null)
            )
          );
        },
        S =
          (n(17),
          function () {
            var e = (function () {
              var e = localStorage.getItem("snp_giv_b_soundcloud_client")
                ? localStorage.getItem("snp_giv_b_soundcloud_client")
                : "";
              return e.length > 0 ? JSON.parse(e) : { enable: !1, expire: 0 };
            })();
            return !!(e.enable && Date.now() < e.expire);
          }),
        D = function (e) {
          var t = e.setEnable;
          return S()
            ? null
            : r.a.createElement(
                "div",
                { className: "twbs" },
                r.a.createElement(
                  "button",
                  {
                    onClick: function () {
                      var e = {
                        enable: !0,
                        expire: Date.now() + f.soundCloudTimeout,
                      };
                      localStorage.setItem(
                        "snp_giv_b_soundcloud_client",
                        JSON.stringify(e)
                      ),
                        t("true");
                    },
                    type: "button",
                    className: "btn btn-secondary btn-sm soundcloud_button",
                  },
                  "Snahp Christmas Tunes on SoundCloud (Uses cookie)"
                )
              );
        },
        x = function (e) {
          var t = Object(a.useState)(S()),
            n = Object(i.a)(t, 2),
            c = n[0],
            o = n[1];
          return r.a.createElement(
            "div",
            { className: "soundcloud_wrapper" },
            r.a.createElement(
              "div",
              { className: "soundcloud_content" },
              r.a.createElement(
                "div",
                { className: "soundcloud_toggle_content" },
                r.a.createElement(D, { setEnable: o })
              ),
              c &&
                r.a.createElement(
                  "div",
                  { className: "soundcloud" },
                  r.a.createElement("iframe", {
                    title: "soundcloud_iframe",
                    width: "100%",
                    height: "340",
                    scrolling: "no",
                    frameBorder: "no",
                    allow: "autoplay",
                    src: "https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/playlists/943663549&color=%23ff5500&auto_play=false&hide_related=true&show_comments=true&show_user=false&show_reposts=false&show_teaser=false&visual=false",
                  })
                )
            )
          );
        },
        C = function (e) {
          var t = e.mode,
            n = e.modeDispatch;
          switch (t) {
            case "after":
              return r.a.createElement(T, null);
            case "show_gift":
              return r.a.createElement(v, null);
            case "countdown":
              return r.a.createElement(E, null);
            case "ready":
              return r.a.createElement(y, null);
            default:
              return (
                (function (e) {
                  fetch(
                    "".concat(
                      f.hostname,
                      "/app.php/snahp/giftbox/unwrap_status/"
                    )
                  )
                    .then(function (e) {
                      return e.json();
                    })
                    .then(function (t) {
                      switch (t.status) {
                        case "after":
                          e({ type: "SET_AFTER", timeLeft: t.time_left });
                          break;
                        case "before":
                        case "not_ready":
                          e({ type: "SET_COUNTDOWN", timeLeft: t.time_left });
                          break;
                        case "ready":
                        default:
                          e({ type: "SET_READY" });
                      }
                    });
                })(n),
                r.a.createElement(r.a.Fragment, null)
              );
          }
        },
        A = function (e) {
          var t = Object(a.useReducer)(u, { mode: "" }),
            n = Object(i.a)(t, 2),
            c = n[0],
            o = n[1],
            l = Object(a.useReducer)(s, {}),
            f = Object(i.a)(l, 2),
            d = f[0],
            h = f[1],
            _ = Object(a.useReducer)(m, []),
            E = Object(i.a)(_, 2),
            g = E[0],
            v = E[1];
          return r.a.createElement(
            p.Provider,
            {
              value: {
                mode: c,
                modeDispatch: o,
                receivedGift: d,
                receivedGiftDispatch: h,
                history: g,
                historyDispatch: v,
              },
            },
            r.a.createElement(N, null),
            r.a.createElement(C, { mode: c.mode, modeDispatch: o }),
            r.a.createElement(x, null)
          );
        },
        I = function (e) {
          return r.a.createElement(
            "div",
            { className: "giftbox-wrapper" },
            r.a.createElement(A, null)
          );
        };
      Boolean(
        "localhost" === window.location.hostname ||
          "[::1]" === window.location.hostname ||
          window.location.hostname.match(
            /^127(?:\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3}$/
          )
      );
      o.a.render(
        r.a.createElement(I, null),
        document.getElementById("christmas-giveaway")
      ),
        "serviceWorker" in navigator &&
          navigator.serviceWorker.ready.then(function (e) {
            e.unregister();
          });
    },
  ],
  [[5, 1, 2]],
]);
//# sourceMappingURL=main.f231784d.chunk.js.map
