import java.util.*;
public class Publisher {
    private List<Subscriber> subscribers = new ArrayList<>();
    private String title;
    private String content;
    private String date;
    public void addSubscriber(Subscriber subscriber){
        subscribers.add(subscriber);
    }

    public void removeSubscriber(Subscriber subscriber){
        subscribers.remove(subscriber);
    }

    public void notifySubscribers(){
        for(Subscriber subscriber:subscribers){
            subscriber.update(title, content, date);
        }
    }

    public void setNews(String title, String content, String date){
        this.title = title;
        this.content = content;
        this.date = date;
        notifySubscribers();
    }
}

