function PusherActivityStreamer(channel, container, options) {
  options = options || {};

  // defaults
  this.settings = {
    events: [],
    maxItems: 10,
    handler: PusherActivityStreamer.defaultActivityHandler
  };
  for (key in options) {
    this.settings[key] = options[key];
  }
  
  this.channel = channel;
  this.container = container;
  
  for (var ii in this.settings.events) {
    var type = this.settings.events[ii];
    var handler = this.settings.handler;

    // wrap in closure to make type stick
    (function bindType(channel, type, handler, self) {
      channel.bind(type, function (activity) {
        handler.call(self, activity, type);
      });
    })(channel, type, handler, this);
  }
  this.count = 0;
};

PusherActivityStreamer.defaultActivityHandler = function(activity, type) {
  ++this.count;
  var li = document.createElement('li');
  // keep timestamp
  li.setAttribute('data-ts', activity.ts);
  // set ts on msg
  li.className = type;
  li.innerHTML = activity.msg;
  this.container.insertBefore(li, this.container.firstChild);
  if (this.count > this.settings.maxItems) {
    this.container.removeChild(this.container.lastChild);
    --this.count;
  }
}
